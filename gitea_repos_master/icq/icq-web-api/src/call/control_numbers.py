import random


class ControlNumberManager:

    numbers = [{
        'phone': '+79208533738',
        'name': 'Максим Ковынёв'
    }, {
        'phone': '+79773539941',
        'name': 'Владислав Петров'
    }]

    @staticmethod
    def get_random():
        return random.choice(ControlNumberManager.numbers)
