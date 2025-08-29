FROM python:3-alpine
WORKDIR /app
COPY . .
RUN pip --no-cache-dir install -r requirements.txt
CMD [ "python", "./main.py" ]