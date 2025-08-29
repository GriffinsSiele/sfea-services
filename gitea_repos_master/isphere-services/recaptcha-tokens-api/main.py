from fastapi.responses import RedirectResponse
from fastapi import FastAPI, Request
import uvicorn
from typing import Optional
from src.mongo import IsphereToken
from settings import APP_PORT, MONGO_HOST, MONGO_PORT, MONGO_DB, MONGO_COLLECTION
from src.requests_classes import v2, v3, hcaptcha

tokens = IsphereToken(f'mongodb://{MONGO_HOST}', MONGO_PORT, MONGO_DB, MONGO_COLLECTION)
mng = FastAPI(title='Tokens API')


@mng.get("/", response_class=RedirectResponse, include_in_schema=False)
async def root():
    return RedirectResponse('/docs')


@mng.get("/v3", tags=['get tokens'])
async def get_v3(sitekey: str, action: str):
    token = tokens.get(sitekey=sitekey, action=action, version='v3')
    if token:
        return {'code': 200, 'token': token['TOKEN']}
    else:
        return {'code': 204}


@mng.get("/v2", tags=['get tokens'])
async def get_v2(sitekey: str):
    token = tokens.get(sitekey=sitekey, version='v2')
    if token:
        return {'code': 200, 'token': token['TOKEN']}
    else:
        return {'code': 204}


@mng.post("/v3", tags=['add tokens'])
async def add_v3(request: Request, options: v3):
    try:
        result = await request.json()
        tokens.add(TOKEN=result['TOKEN'], sitekey=result['sitekey'], action=result['action'], version='v3')
        return {'code': 200}
    except Exception as e:
        return e


@mng.post("/v2", tags=['add tokens'])
async def add_v2(request: Request, options: v2):
    try:
        result = await request.json()
        tokens.add(TOKEN=result['TOKEN'], sitekey=result['sitekey'], version='v2')
        return {'code': 200}
    except Exception as e:
        return e


@mng.get("/count/v2", tags=['count tokens'])
async def count_v2(sitekey: str):
    try:
        return {'code': 200, 'count': tokens.count(sitekey=sitekey, version='v2')}
    except Exception as e:
        return e


@mng.get("/count/v3", tags=['count tokens'])
async def count_v3(sitekey: str, action: str):
    try:
        return {'code': 200, 'count': tokens.count(sitekey=sitekey, version='v3', action=action)}
    except Exception as e:
        return e


@mng.get("/count/hcaptcha", tags=['count tokens'])
async def count_hcaptcha(sitekey: str):
    try:
        return {'code': 200, 'count': tokens.count(sitekey=sitekey, version='hcaptcha')}
    except Exception as e:
        return e


@mng.get("/hcaptcha", tags=['get tokens'])
async def get_hcaptcha(sitekey: str):
    token = tokens.get(sitekey=sitekey, version='hcaptcha')
    if token:
        return {'code': 200, 'token': token['TOKEN']}
    else:
        return {'code': 204}


@mng.post("/hcaptcha", tags=['add tokens'])
async def add_hcaptcha(request: Request, options: hcaptcha):
    try:
        result = await request.json()
        tokens.add(TOKEN=result['TOKEN'], sitekey=result['sitekey'], version='hcaptcha')
        return {'code': 200}
    except Exception as e:
        return e


if __name__ == '__main__':
    uvicorn.run('main:mng', host='0.0.0.0', port=APP_PORT, reload=True)
