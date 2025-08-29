import os
import random

from putils_logic.putils import PUtils

from src.utils.metaclasses import Singleton


class DialogGenerator(metaclass=Singleton):
    def __init__(self):
        with open(PUtils.bp(os.path.abspath(__file__), "..", "dialog.txt")) as f:
            data = f.readlines()
        self.variants = [v.strip() for v in data]

    def generate_message(self):
        return random.choice(self.variants)
