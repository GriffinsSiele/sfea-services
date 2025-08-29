FROM python:3-alpine
WORKDIR /app
COPY . .
RUN pip install pipenv && pipenv install --system --deploy && pip install typing_extensions && pip uninstall pipenv -y
CMD [ "python", "./main.py" ]
