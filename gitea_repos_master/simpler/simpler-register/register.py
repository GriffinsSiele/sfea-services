from datetime import datetime

from tqdm import tqdm

from src.devices.generator import DeviceGenerator
from src.email.generator import EmailHashGenerator
from src.request_params.request import RegisterParams
from src.utils.yaml import YamlFile

COUNT_ACCOUNTS = 10

for i in tqdm(range(COUNT_ACCOUNTS)):
    fcm_token = "fL4uTjHkRl675yWJd9E9a6:APA91bH1eAsqcfWWPcfiJW6Rbzdt0twp6F1Zj--jVKvjtYuomyqFG97q0WNeSqKFe7HK3jN5XyTWgxd2p94xKS1dnBxRnlwzHud1Ff10l9rl26PvmHGufOMTMeHDxDKue3goPzvpYr-1"
    email, hash = EmailHashGenerator.generate()
    hash_list = [hash]
    rp = RegisterParams(DeviceGenerator.generate(), fcm_token, hash_list)

    response = rp.request()

    now = datetime.now()
    day = now.strftime("%Y_%m_%d")

    YamlFile(f'./data/data_{day}.yml').append([{
        'token': response.json()['authentication_token'],
        'timestamp': now,
        'email': email
    }])
