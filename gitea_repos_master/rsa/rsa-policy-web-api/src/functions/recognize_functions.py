import logging

import cv2
import easyocr
import pytesseract
from numpy import ndarray

from src.config.custom_types import ListImages
from src.config.standard_config import StandardisationConfig


class RecognizeFunctions:

    EASYOCR_MODELS = StandardisationConfig.easyocr_models
    AUTO_RUSSIAN_WORDS = StandardisationConfig.auto_russian_words

    reader_en = easyocr.Reader(
        ["en"], verbose=False, model_storage_directory=EASYOCR_MODELS
    )
    reader_ru = easyocr.Reader(
        ["ru"], verbose=False, model_storage_directory=EASYOCR_MODELS
    )

    @staticmethod
    def recognize(img: ndarray, lang: str, config: str = r"--oem 3 --psm 6") -> str:
        value = (
            pytesseract.image_to_string(img, lang=lang, config=config)
            .replace("\n", " ")
            .replace("\f", "")[:-1]
        )
        return value

    @staticmethod
    def __get_image_data(bin_img, lang):
        return pytesseract.image_to_data(bin_img, lang=lang, output_type="dict")

    @staticmethod
    def __word_coords(data):
        word_coords = []
        for i in range(len(data["text"])):
            if data["text"][i] and (
                not any(bracket in data["text"][i] for bracket in ("(", ")"))
            ):

                word_coord = (
                    data["left"][i],
                    data["top"][i],
                    data["width"][i],
                    data["height"][i],
                )
                word_coords.append(word_coord)

        return word_coords

    @classmethod
    def __pars_auto(cls, bin_img, word_coords):
        pars_words = []
        russian_words = cls.AUTO_RUSSIAN_WORDS

        for coord in word_coords:
            word = bin_img[coord[1] : coord[1] + coord[3], coord[0] : coord[0] + coord[2]]
            recognize_word = cv2.copyMakeBorder(
                word, 10, 10, 10, 10, borderType=cv2.BORDER_CONSTANT, value=255
            )
            rus_value = cls.recognize(recognize_word, lang="rus").strip()
            eng_value = cls.recognize(recognize_word, lang="eng").strip()
            if rus_value in russian_words:
                parse_value = rus_value
            else:
                parse_value = eng_value
            pars_words.append(parse_value)
        result = " ".join(pars_words)
        return result

    @classmethod
    def auto_recognize(cls, list_img, i):
        return_string = ""
        img = list_img[i]
        data = cls.__get_image_data(img, lang="eng")
        if data["text"]:
            auto_category = data["text"][-1]
            word_cords = cls.__word_coords(data)
            if word_cords:
                value = cls.__pars_auto(img, word_cords)
                return_string = f"{value} {auto_category}"
            else:
                logging.warning(
                    f"! The text matching the conditions was not found: \"{data['text']}\""
                )
        else:
            logging.warning(f"! Failed to recognize correctly: \"{data['text']}\"")
        return return_string

    @staticmethod
    def __owner_name(data):
        word_cords = []
        for i in range(len(data["text"])):
            if data["text"][i] and (not data["text"][i].replace(".", "").isdigit()):

                word_coord = (
                    data["left"][i],
                    data["top"][i],
                    data["width"][i],
                    data["height"][i],
                )
                word_cords.append(word_coord)
        return word_cords[:3]

    @staticmethod
    def __get_names(img, coord):
        word = img[coord[1] : coord[1] + coord[3], coord[0] : coord[0] + coord[2]]
        symbol = word[:, :20]
        recognize_simbol = cv2.copyMakeBorder(
            symbol, 8, 8, 8, 8, borderType=cv2.BORDER_CONSTANT, value=255
        )
        rus_symbol = (
            pytesseract.image_to_string(
                recognize_simbol, lang="rus", config=r"--oem 3 --psm 10"
            )
            .replace("\n", " ")
            .replace("\f", "")[:-1]
        )
        name = f"{rus_symbol[0].upper()}{'*' * 4}"
        return name

    @classmethod
    def owner_recognize(cls, list_img: ListImages, i: int):

        img = list_img[i]
        reader_data = cls.reader_ru.readtext(img)
        reader_list = [data[1] for data in reader_data]
        reader_text = " ".join(reader_list)

        if "ИНН" not in reader_text:
            coord_data = cls.__get_image_data(img, lang="rus")
            word_cords = cls.__owner_name(coord_data)
            word_list = reader_text.split(" ")
            birth_day = word_list[-1]
            char_list = word_list[:-1]
            name_list = [
                char_list[i] for i in range(len(char_list)) if char_list[i] not in [""]
            ]
            name_list = name_list[:3]
            if len(word_cords) == len(name_list):
                for i in range(len(name_list)):
                    if not name_list[i][0].isalpha():
                        word_coord = word_cords[i]
                        name = cls.__get_names(img, coord=word_coord)
                        name_list[i] = name

            elif len(word_cords) > len(name_list):
                name_list = []
                for word_coord in word_cords:
                    name = cls.__get_names(img, coord=word_coord)
                    name_list.append(name)
            reader_text = f"{' '.join(name_list)} {birth_day}"
        return reader_text

    @classmethod
    def recognize_plate(cls, list_img: ListImages, i: int):
        img = list_img[i]
        result = cls.reader_en.readtext(img)  # easyOCR
        char_list = list(result[0][1])
        if len(char_list) > 15:
            value = cls.recognize(img, lang="rus")
        else:
            value = "".join(char_list)
        return value

    @classmethod
    def recognize_vin(cls, list_img: ListImages, i: int):
        img = list_img[i]
        results = cls.reader_en.readtext(img)  # easyOCR
        str_list = [result[1] for result in results]
        value = "".join(str_list)
        return value

    @classmethod
    def recognize_rus(cls, list_img: ListImages, i: int) -> str:
        lang = "rus"
        img = list_img[i]
        value = cls.recognize(img, lang)
        return value

    @classmethod
    def recognize_kbm(cls, list_img: ListImages, i: int) -> str:
        lang = "rus"
        img = list_img[i]
        value = cls.recognize(img, lang)
        return value

    @classmethod
    def recognize_eng(cls, list_img: ListImages, i: int) -> str:
        lang = "eng"
        img = list_img[i]
        value = cls.recognize(img, lang)
        return value

    @staticmethod
    def recognize_number(list_img: ListImages, i: int) -> str:
        return f"{i + 1}"
