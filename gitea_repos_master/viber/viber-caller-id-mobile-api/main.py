import requests

response = requests.get("https://api.myip.com")
print(f"User: {response.text}")
