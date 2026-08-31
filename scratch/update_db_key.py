import psycopg2

def main():
    import os
    pwd = os.getenv('NEON_PASSWORD')
    working_key = os.getenv('GEMINI_API_KEY')
    if not pwd or not working_key:
        raise RuntimeError('NEON_PASSWORD/GEMINI_API_KEY missing')
    conn = psycopg2.connect(
        host='ep-crimson-sun-ac4t9f9a-pooler.sa-east-1.aws.neon.tech',
        database='neondb',
        user='neondb_owner',
        password=pwd,
        port=5432,
        sslmode='require'
    )
    cursor = conn.cursor()
    
    # 1. Update the key
    print(f"Updating Gemini API key in Neon database to: {working_key[:8]}...{working_key[-8:]}")
    
    cursor.execute(
        "UPDATE configuracao_empresa SET gemini_api_key = %s WHERE id = 'principal';",
        (working_key,)
    )
    conn.commit()
    print("Update successful!")
    
    # 2. Verify update
    cursor.execute("SELECT gemini_api_key FROM configuracao_empresa WHERE id = 'principal' LIMIT 1;")
    updated_key = cursor.fetchone()[0]
    print(f"Verified key in DB: {updated_key[:8]}...{updated_key[-8:] if updated_key else ''} (Length: {len(updated_key) if updated_key else 0})")
    
    cursor.close()
    conn.close()

if __name__ == '__main__':
    main()
