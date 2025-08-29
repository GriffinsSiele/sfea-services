FROM git.i-sphere.local/isphere-images/python:3.11-slim-buster
WORKDIR /app
ARG GIT_HTTP_URL_WITH_CREDENTIALS
COPY . .
RUN pip install --no-cache-dir pipenv==2022.1.8 && \
    pipenv install --skip-lock --system --deploy && \
    pip uninstall pipenv -y && \
    apt-get update && apt-get install --no-install-recommends -y nodejs=10.24.0~dfsg-1~deb10u4 && \
    apt-get clean && rm -rf /var/lib/apt/lists/*
RUN chmod +x start.sh
CMD ["./start.sh"]