import urllib.request
import urllib.error

def main():
    url = "https://wedistinto.com/sistema/p.php?slug=igor-dias-e-gabriela-viana-casamento-DVZJG5UV"
    print(f"Fetching: {url}")
    try:
        req = urllib.request.Request(
            url,
            headers={
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            }
        )
        with urllib.request.urlopen(req) as response:
            html = response.read().decode('utf-8', errors='ignore')
            print(f"Response code: {response.status}")
            print(f"Response length: {len(html)}")
            print("\n--- HTML LAST 1000 CHARACTERS ---")
            print(html[-1500:])
            print("\n--- SEARCH FOR FATAL ERROR / WARNING ---")
            for line in html.split('\n'):
                if any(x in line.lower() for x in ['fatal error', 'warning', 'notice', 'exception', 'stack trace', 'error:']):
                    print(line)
    except urllib.error.HTTPError as e:
        print(f"HTTP Error: {e.code}")
        print(e.read().decode('utf-8', errors='ignore'))
    except Exception as e:
        print(f"Error: {e}")

if __name__ == '__main__':
    main()
