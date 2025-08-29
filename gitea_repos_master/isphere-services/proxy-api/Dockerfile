FROM python:3.10-alpine
WORKDIR /app
ARG GIT_USER
ARG GIT_TOKEN
COPY . .
RUN pip install --no-cache-dir pipenv==2022.1.8 && pipenv install --skip-lock --system && pip uninstall pipenv -y
CMD ["uvicorn", "main:app", "--log-config=config/log_conf.yml", "--host=0.0.0.0"]