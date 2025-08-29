import json
import os

from request_logic.path_utils import PUtils


class TemplatePicker:
    templates = PUtils.bp(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'templates')

    @staticmethod
    def get(name):
        file = PUtils.bp(TemplatePicker.templates, f'{name}.json')
        if PUtils.is_file_exists(file):
            return json.load(open(file))
        raise Exception(f'Not found template with name: {name}')
