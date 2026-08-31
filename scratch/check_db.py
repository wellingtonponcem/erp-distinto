import psycopg2

def main():
    import os
    pwd = os.getenv('NEON_PASSWORD')
    if not pwd:
        raise RuntimeError('NEON_PASSWORD missing')
    try:
        conn = psycopg2.connect(
            host='ep-crimson-sun-ac4t9f9a-pooler.sa-east-1.aws.neon.tech',
            database='neondb',
            user='neondb_owner',
            password=pwd,
            port=5432,
            sslmode='require'
        )
        cursor = conn.cursor()
        cursor.execute("SELECT id, nome, gemini_api_key, groq_api_key, openrouter_api_key FROM configuracao_empresa;")
        rows = cursor.fetchall()
        for row in rows:
            print(f"ID: {row[0]}")
            print(f"Nome: {row[1]}")
            print(f"Gemini Key: {row[2][:10] if row[2] else 'None'}... (Length: {len(row[2]) if row[2] else 0})")
            print(f"Groq Key: {row[3][:10] if row[3] else 'None'}...")
            print(f"OpenRouter Key: {row[4][:10] if row[4] else 'None'}...")
            print("-" * 50)
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    main()
