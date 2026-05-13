#!/usr/bin/env python3
"""Convert MySQL dump to PostgreSQL-compatible SQL for Neon."""
import re
import sys


def convert_column_types(line):
    """Apply all MySQL→PostgreSQL type conversions to a column definition line."""
    # longtext / mediumtext / tinytext -> TEXT
    line = re.sub(r'\b(long|medium|tiny)text\b', 'TEXT', line, flags=re.IGNORECASE)
    # int(N) -> INTEGER   (no trailing \b because ) is not a word char)
    line = re.sub(r'\bint\(\d+\)', 'INTEGER', line, flags=re.IGNORECASE)
    # tinyint(N) -> SMALLINT
    line = re.sub(r'\btinyint\(\d+\)', 'SMALLINT', line, flags=re.IGNORECASE)
    # enum(...) -> VARCHAR(100)
    line = re.sub(r"\benum\([^)]+\)", "VARCHAR(100)", line, flags=re.IGNORECASE)
    # timestamp NULL -> TIMESTAMP
    line = re.sub(r'\btimestamp\s+NULL\b', 'TIMESTAMP', line, flags=re.IGNORECASE)
    # current_timestamp() -> CURRENT_TIMESTAMP
    line = re.sub(r'\bcurrent_timestamp\(\)', 'CURRENT_TIMESTAMP', line, flags=re.IGNORECASE)
    return line


def convert_column(line, table_name):
    """Convert a MySQL column definition line to PostgreSQL."""
    has_comma = line.endswith(',')
    line = line.rstrip(',').strip()

    # Remove backticks from column name
    line = re.sub(r'`(\w+)`', r'\1', line)

    line = convert_column_types(line)

    # Special: memorias.id with int NOT NULL -> SERIAL NOT NULL
    if table_name == 'memorias' and re.match(r'^id\s+INTEGER\s+NOT NULL', line):
        line = 'id SERIAL NOT NULL'

    return line, has_comma


def convert(content):
    lines = content.split('\n')

    # --- Pass 1: collect primary keys per table from ALTER TABLE ---
    primary_keys = {}
    current_alter_table = None
    for line in lines:
        m = re.match(r"ALTER TABLE `(\w+)`\s*$", line.strip())
        if m:
            current_alter_table = m.group(1)
        if current_alter_table:
            m2 = re.match(r"\s*ADD PRIMARY KEY \(`([^`]+)`\)[,;]?$", line)
            if m2:
                primary_keys[current_alter_table] = m2.group(1)

    # --- Pass 2: convert ---
    out = []
    out.append("-- PostgreSQL dump converted from MySQL (MariaDB)")
    out.append("-- Target: Neon PostgreSQL")
    out.append("")
    out.append("SET standard_conforming_strings = off;")
    out.append("BEGIN;")
    out.append("")

    current_create_table = None
    in_create_table = False
    pending_indexes = []
    alter_table_ctx = None

    i = 0
    while i < len(lines):
        line = lines[i]
        stripped = line.strip()

        # Skip MySQL-specific header/footer directives
        if stripped.startswith('/*!'):
            i += 1
            continue
        if stripped in ('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
                        'START TRANSACTION;',
                        'COMMIT;',
                        'SET time_zone = "+00:00";'):
            i += 1
            continue
        # phpMyAdmin version/meta comment lines
        if re.match(r'^-- (phpMyAdmin|version \d|https://|Host:|Tempo de|Vers|Banco de dados)', stripped):
            i += 1
            continue

        # --- CREATE TABLE ---
        m_create = re.match(r"CREATE TABLE `(\w+)` \($", stripped)
        if m_create:
            current_create_table = m_create.group(1)
            in_create_table = True
            out.append(f"CREATE TABLE {current_create_table} (")
            i += 1
            continue

        if in_create_table:
            # Closing ) ENGINE=...
            if re.match(r'\) ENGINE=', stripped):
                pk = primary_keys.get(current_create_table)
                if pk:
                    # Ensure last column line has a comma
                    if out and not out[-1].endswith(','):
                        out[-1] = out[-1] + ','
                    out.append(f"  PRIMARY KEY ({pk})")
                out.append(");")
                out.append("")
                in_create_table = False
                current_create_table = None
                i += 1
                continue

            col_line, has_comma = convert_column(stripped, current_create_table)
            if col_line:
                suffix = ',' if has_comma else ''
                out.append(f"  {col_line}{suffix}")
            i += 1
            continue

        # --- INSERT INTO ---
        if stripped.startswith('INSERT INTO `'):
            converted = re.sub(r'`(\w+)`', r'\1', line)
            out.append(converted)
            i += 1
            continue

        # --- ALTER TABLE ---
        m_alter = re.match(r"ALTER TABLE `(\w+)`\s*$", stripped)
        if m_alter:
            alter_table_ctx = m_alter.group(1)
            i += 1
            continue

        if alter_table_ctx and stripped:
            # ADD PRIMARY KEY – already inlined, skip
            if re.match(r'ADD PRIMARY KEY', stripped):
                if line.rstrip().endswith(';'):
                    alter_table_ctx = None
                i += 1
                continue

            # MODIFY ... AUTO_INCREMENT -> setval
            m_mod = re.match(r'MODIFY `id` int\(\d+\) NOT NULL AUTO_INCREMENT,\s*AUTO_INCREMENT=(\d+)[;,]?', stripped)
            if m_mod:
                seq_val = int(m_mod.group(1)) - 1
                out.append(f"SELECT setval('{alter_table_ctx}_id_seq', {seq_val});")
                if line.rstrip().endswith(';'):
                    alter_table_ctx = None
                i += 1
                continue

            # ADD KEY name (col) -> CREATE INDEX
            m_key = re.match(r'ADD KEY `(\w+)` \(`(\w+)`\)[,;]?$', stripped)
            if m_key:
                idx_name, col = m_key.group(1), m_key.group(2)
                pending_indexes.append(f"CREATE INDEX {idx_name} ON {alter_table_ctx} ({col});")
                if stripped.endswith(';'):
                    alter_table_ctx = None
                i += 1
                continue

            # ADD UNIQUE KEY name (col) -> CREATE UNIQUE INDEX
            m_ukey = re.match(r'ADD UNIQUE KEY `(\w+)` \(`(\w+)`\)[,;]?$', stripped)
            if m_ukey:
                idx_name, col = m_ukey.group(1), m_ukey.group(2)
                pending_indexes.append(f"CREATE UNIQUE INDEX {idx_name} ON {alter_table_ctx} ({col});")
                if stripped.endswith(';'):
                    alter_table_ctx = None
                i += 1
                continue

            # ADD CONSTRAINT FOREIGN KEY -> keep, clean backticks
            if re.match(r'ADD CONSTRAINT', stripped):
                fk_line = re.sub(r'`(\w+)`', r'\1', stripped)
                out.append(f"ALTER TABLE {alter_table_ctx}")
                out.append(f"  {fk_line}")
                if stripped.endswith(';'):
                    alter_table_ctx = None
                i += 1
                continue

            # Semicolon alone = end of block
            if stripped == ';':
                alter_table_ctx = None
                i += 1
                continue

            # Skip other ALTER TABLE contents
            i += 1
            continue

        # Flush pending indexes on blank line after ALTER TABLE section
        if stripped == '' and pending_indexes:
            for idx in pending_indexes:
                out.append(idx)
            pending_indexes = []
            out.append('')
            i += 1
            continue

        # Regular comment lines
        if stripped.startswith('--'):
            # Clean backticks from comments too
            out.append(re.sub(r'`(\w+)`', r'\1', line))
            i += 1
            continue

        # Blank lines
        if stripped == '':
            out.append('')
            i += 1
            continue

        # Anything else – keep as-is
        out.append(line)
        i += 1

    # Flush any remaining indexes
    if pending_indexes:
        for idx in pending_indexes:
            out.append(idx)

    out.append("")
    out.append("COMMIT;")
    return '\n'.join(out)


if __name__ == '__main__':
    input_file = sys.argv[1] if len(sys.argv) > 1 else 'u306254544_distinto.sql'
    output_file = sys.argv[2] if len(sys.argv) > 2 else 'distinto_neon.sql'

    with open(input_file, 'r', encoding='utf-8') as f:
        content = f.read()

    result = convert(content)

    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(result)

    print(f"Convertido: {input_file} -> {output_file}")
    print(f"Linhas no arquivo gerado: {result.count(chr(10))}")
