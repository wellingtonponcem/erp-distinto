import urllib.request
import json
import psycopg2

def main():
    # 1. Fetch key from DB
    conn = psycopg2.connect(
        host='ep-crimson-sun-ac4t9f9a-pooler.sa-east-1.aws.neon.tech',
        database='neondb',
        user='neondb_owner',
        password='npg_3fXdCHMbS2xJ',
        port=5432,
        sslmode='require'
    )
    cursor = conn.cursor()
    cursor.execute("SELECT gemini_api_key FROM configuracao_empresa WHERE id='principal' LIMIT 1;")
    key = cursor.fetchone()[0]
    cursor.close()
    conn.close()

    print(f"Testing Gemini Key: {key[:8]}...{key[-8:] if key else ''}")
    
    # 2. Call Gemini API
    url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={key}"
    payload = {
        'contents': [{'parts': [{'text': 'Hello'}]}],
        'generationConfig': {
            'temperature': 0.3,
            'maxOutputTokens': 100
        }
    }
    
    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode('utf-8'),
        headers={'Content-Type': 'application/json'},
        method='POST'
    )
    
    try:
        with urllib.request.urlopen(req) as response:
            res_body = response.read().decode('utf-8')
            print("SUCCESS:")
            print(res_body)
    except urllib.error.HTTPError as e:
        print(f"HTTP ERROR: {e.code}")
        print(e.read().decode('utf-8'))
    except Exception as e:
        print(f"OTHER ERROR: {e}")

if __name__ == '__main__':
    main()
