import urllib.request
import json
import os

def main():
    import os
    key = os.getenv('GEMINI_API_KEY')
    if not key:
        raise RuntimeError('GEMINI_API_KEY missing')
    print(f"Testing environment variable Gemini Key: {key[:8]}...{key[-8:]}")
    
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
