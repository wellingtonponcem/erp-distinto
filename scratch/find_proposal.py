import psycopg2

def main():
    conn = psycopg2.connect(
        host='ep-crimson-sun-ac4t9f9a-pooler.sa-east-1.aws.neon.tech',
        database='neondb',
        user='neondb_owner',
        password='npg_3fXdCHMbS2xJ',
        port=5432,
        sslmode='require'
    )
    cursor = conn.cursor()
    
    # 1. Search for proposals matching "igor-dias"
    search_query = "%igor%"
    cursor.execute("SELECT id, slug, cliente_nome, titulo, tipo, status FROM propostas WHERE slug LIKE %s OR id LIKE %s OR cliente_nome LIKE %s;", (search_query, search_query, search_query))
    rows = cursor.fetchall()
    print("MATCHING PROPOSALS:")
    for row in rows:
        print(f"ID: {row[0]}, Slug: {row[1]}, Cliente: {row[2]}, Titulo: {row[3]}, Tipo: {row[4]}, Status: {row[5]}")
    
    print("\nALL RECENT PROPOSALS (limit 5):")
    cursor.execute("SELECT id, slug, cliente_nome, titulo, created_at FROM propostas ORDER BY created_at DESC LIMIT 5;")
    for row in cursor.fetchall():
        print(f"ID: {row[0]}, Slug: {row[1]}, Cliente: {row[2]}, Titulo: {row[3]}, Created At: {row[4]}")
        
    cursor.close()
    conn.close()

if __name__ == '__main__':
    main()
