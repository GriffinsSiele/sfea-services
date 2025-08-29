FROM git.i-sphere.local/isphere-images/docker_python_with_tesseract:5.3-3.11

ENV OMP_THREAD_LIMIT = 1
ENV PIPENV_VENV_IN_PROJECT = 1

ARG GIT_HTTP_URL_WITH_CREDENTIALS
WORKDIR /image_recognition

COPY Pipfile Pipfile
COPY Pipfile.lock Pipfile.lock

RUN pip install --no-cache-dir pipenv==2022.1.8
RUN pipenv install easyocr==1.7.1
RUN pipenv install --skip-lock --system --deploy

COPY . .