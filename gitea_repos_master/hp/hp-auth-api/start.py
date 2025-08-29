import requests

if __name__ == "__main__":
    response = requests.get("https://api.myip.com")
    print(f"User IP: {response.text}")