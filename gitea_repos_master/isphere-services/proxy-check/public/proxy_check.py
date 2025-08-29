#!/usr/bin/python3
import aiohttp
from aiohttp_proxy import ProxyConnector
import asyncio
import time
import os
import logging

logging.basicConfig(
    format="%(asctime)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s",
    level=logging.INFO,
)
user = os.environ['USERNAME']
password = os.environ['PASSWORD']

async def get_proxies(session):
    async with session.get(f'http://get-proxies-master.main-service.svc.cluster.local/2.00/get_proxies.php') as resp:
        return await resp.json()

async def request(session,proxy):
    try:
        async with session.get('https://i-sphere.ru/2.00/my-ip.php') as responce:
            return await responce.text()
    except Exception as e:
        logging.exception(proxy)
        logging.exception(e)
        return None


async def fetch(proxy):
    connector=ProxyConnector(host=proxy['server'],port=proxy['port'],username=proxy['login'],password=proxy['password'])
    session_timeout = aiohttp.ClientTimeout(total=10,sock_connect=5,sock_read=5)
    async with aiohttp.ClientSession(connector=connector,timeout=session_timeout) as session:
        ip = await request(session,proxy)
        if not ip:
            await asyncio.sleep(15)
            ip = await request(session,proxy)
        if not ip:
            return{'reject':proxy['id']}
        return {'success':proxy['id']}

def sort_ids(ans):
    result=[[],[]]
    for i in ans:
        if i.get('reject'):
            result[0].append(i.get('reject'))
        else:
            result[1].append(i.get('success'))
    results=[None,None]
    results[0] = ','.join(result[0])
    results[1] = ','.join(result[1])
    return results

async def chenck_proxy():
    async with aiohttp.ClientSession(auth=aiohttp.BasicAuth(user, password)) as session:
        proxies = await get_proxies(session)
        ans = await asyncio.gather(*(fetch(proxy) for proxy in proxies))
        # reject = [i.get('reject') for i in ans if i.get('reject')]
        # pprint(ans)
        ids=sort_ids(ans)
        # pprint(ids)
        for i in range(len(ids)):
            if ids[i]:  
                async with session.get(f'http://get-proxies-master.main-service.svc.cluster.local/2.00/get_proxies.php?id={ids[i]}&report={i}') as resp:
                    print(resp.url,resp.status)        
    

if __name__ == '__main__':
    start = time.time()
    loop = asyncio.get_event_loop()
    result = loop.run_until_complete(chenck_proxy())
    # asyncio.run(chenck_proxy())
    print('time=',time.time() - start)

