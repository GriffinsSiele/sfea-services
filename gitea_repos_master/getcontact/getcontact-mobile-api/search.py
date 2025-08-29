from src.call.search import Search

phone_number = '+79992295925'
token, aes_key, device_id = "mRurp8ad3fb7957cb34535dca6b3a3c92a031f9ea878e85da1c1765ebbb", "c88adaac1f021410bfb1c6f02be7c06f18c8bbc0736a7692a6b12ae1d5aa34c2", "76931fac9dab2b36"
token, aes_key, device_id = "IqHHndb8fe9ceeb00ef1c4ec9021c09d6a30a48048fa52583b5dbb9b75d", "1b623e93103abefc4d070b8ad376e20385fe6f70a98f4a0b9d79acb17d032a0f", "4320b33858831e8f"

s = Search(device_id, token, aes_key)
response = s.search_phone_number(phone_number)
print(response)
