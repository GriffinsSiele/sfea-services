FROM  python:3-slim-bullseye
WORKDIR /app
EXPOSE 8001
COPY . .
RUN pip install --no-cache -r ./requirements.txt
CMD [ "python", "./main.py" ]