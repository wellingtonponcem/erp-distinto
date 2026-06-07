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
    
    # 1. Update the key
    working_key = 'AIzaSyCEK588doDrGt5myWeF3BmfRDd4o_aXTaA'
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
