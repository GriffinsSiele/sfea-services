FROM python:3.10-alpine
WORKDIR /app
ARG GIT_USER
ARG GIT_TOKEN
COPY . .
RUN pip install --no-cache-dir pipenv==2022.1.8 && \
    pipenv install --skip-lock --system --deploy && \
    pip uninstall pipenv -y
CMD [ "python", "./start.py" ]