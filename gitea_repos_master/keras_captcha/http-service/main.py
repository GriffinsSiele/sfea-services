from Capchadecode.neural import captchadecode
from fastapi.responses import RedirectResponse
from fastapi import FastAPI, File, UploadFile
import uvicorn
import time

fms=captchadecode(h5='models/pred_model_fms.h5',resolution=(198,60),answer_lenght=6,characters=['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'])
gosuslugi=captchadecode(h5='models/pred_model_gosuslugi.h5',resolution=(255,75),answer_lenght=7,characters=['4', '_', 'я', 'ц', 'д', 'и', 'ю', '1', 'й', 'ф', 'ш', '2', 'п', '6', '8', 'г', '9', '7', 'л', 'ж', '5', 'э'])
gibdd=captchadecode(h5='models/pred_model_gibdd.h5',resolution=(150,80),answer_lenght=5,characters=['2', '6', '9', '8', '1', '5', '0', '4', '3', '7'])
fns=captchadecode(h5='models/pred_model_fns.h5',resolution=(200,100),answer_lenght=6,characters=['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'])
vk=captchadecode(h5='models/pred_model_vk.h5',resolution=(130,50),answer_lenght=7,characters=['m', 'p', 'q', 'u', '7', 'e', '4', 'z', 'n', 'k', 'D', 'y', 'Q', 'a', 's', '8', 'x', 'v', '2', 'P', '5', '_', 'X', 'C', 'Z', 'c', 'h', 'S', 'd', 'V'])
mvd=captchadecode(h5='models/pred_model_mvd.h5',resolution=(200,60),answer_lenght=5,characters=['э', 'я', 'д', '8', '9', '4', '5', 'м', 'т', 'б', 'л', 'п', '2', '6', 'и', 'а', '7', 'н', 'ю', 'е', 'г', 'у', 'к', 'р', 'ж', 'в', 'с'])
fsspsite=captchadecode(h5='models/pred_model_fsspsite.h5',resolution=(200,60),answer_lenght=5,characters=['2', '4', '5', '6', '7', '8', '9', 'б', 'в', 'г', 'д', 'ж', 'к', 'л', 'м', 'н', 'п', 'р', 'с', 'т'])
getcontact=captchadecode(h5='models/pred_model_getcontact.h5',resolution=(120,40),answer_lenght=6,characters=['F', 'k', 'Z', 'b', '8', '6', 'p', 't', 'l', 'S', '3', 'L', 'w', 'T', 'V', 'Y', 'W', 'D', 'q', 'I', 'B', 'x', 'u', 'r', 'd', 'G', 'N', '2', '5', 'Q', 'R', 'm', '9', 'U', 'g', 'f', 's', 'i', 'C', '1', 'O', 'J', 'X', 'H', 'v', 'j', 'o', '4', 'e', 'y', '7', 'P', 'h', 'z', 'E', 'K', 'A', 'M', 'n', 'a', 'c'])

app = FastAPI(title='CAPTCHA')

@app.get("/",response_class=RedirectResponse,include_in_schema=False)
async def hello():
    return RedirectResponse('/docs')

@app.post("/fmsdecode",tags=['imageCaptcha'])
async def fmsdecode(image: UploadFile = File(...)):
    start=time.time()
    content = await image.read()
    pred_texts = fms.run(content)
    stop=time.time() - start
    return {"text": pred_texts[0].replace('[UNK]','-'),"time":stop}

@app.post("/gosuslugidecode",tags=['imageCaptcha'])
async def gosuslugidecode(image: UploadFile = File(...)):
    start=time.time()
    content = await image.read()
    pred_texts = gosuslugi.run(content)
    stop=time.time() - start
    return {"text": pred_texts[0].replace('_','').replace('[UNK]','-'),"time":stop}

@app.post("/gibdddecode",tags=['imageCaptcha'])
async def gibdddecode(image: UploadFile = File(...)):
    start=time.time()
    content = await image.read()
    pred_texts = gibdd.run(content)
    stop=time.time() - start
    return {"text": pred_texts[0].replace('_','').replace('[UNK]','-'),"time":stop}


@app.post("/fnsdecode",tags=['imageCaptcha'])
async def fnsdecode(image: UploadFile = File(...)):
    start=time.time()
    content = await image.read()
    pred_texts = fns.run(content)
    stop=time.time() - start
    return {"text": pred_texts[0].replace('[UNK]','-'),"time":stop}

@app.post("/vkdecode",tags=['imageCaptcha'])
async def vkdecode(image: UploadFile = File(...)):
    start=time.time()
    content = await image.read()
    pred_texts = vk.run(content)[0].replace('_','').replace('[UNK]','-')
    stop=time.time() - start
    return {"text": pred_texts,"time":stop}

@app.post("/mvddecode",tags=['imageCaptcha'])
async def mvddecode(image: UploadFile = File(...)):
    start=time.time()
    content = await image.read()
    pred_texts = mvd.run(content)
    stop=time.time() - start
    return {"text": pred_texts[0].replace('[UNK]','-'),"time":stop}

@app.post("/fsspsitedecode",tags=['imageCaptcha'])
async def fsspsitedecode(image: UploadFile = File(...)):
    start=time.time()
    content = await image.read()
    pred_texts = fsspsite.run(content)
    stop=time.time() - start
    return {"text": pred_texts[0].replace('[UNK]','-'),"time":stop}

@app.post("/gcdecode",tags=['imageCaptcha'])
async def gcdecode(image: UploadFile = File(...)):
    start=time.time()
    content = await image.read()
    pred_texts = getcontact.run(content)
    stop=time.time() - start
    return {"text": pred_texts[0].replace('[UNK]','-'),"time":stop}

if __name__ == '__main__':
    uvicorn.run('main:app', host='0.0.0.0', port=8001)
