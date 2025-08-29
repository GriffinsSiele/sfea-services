from bs4 import BeautifulSoup
from pydash import get

from src.logic.adapters.image import ImageAdapter


class BS4Adapter:
    PARSER_PATTERNS = {
        "title": "div.tgme_page_title > span[dir=auto]",
        "verified": "div.tgme_page_title > i.verified-icon",
        "description": "div.tgme_page_description",
        "image": "img.tgme_page_photo_image[src]",
    }

    @staticmethod
    def cast(page: str):
        if not page:
            return None
        soup = BeautifulSoup(page, "html.parser")

        description = BS4Adapter.__extract_data(
            soup, BS4Adapter.PARSER_PATTERNS["description"]
        )
        description = BS4Adapter.__to_str(description)

        name = BS4Adapter.__extract_data(soup, BS4Adapter.PARSER_PATTERNS["title"])
        name = BS4Adapter.__to_str(name)

        if name:
            first_name = " ".join(name.split(" ")[0:-1])
            last_name = name.split(" ")[-1]
        else:
            first_name, last_name = None, None

        verified = BS4Adapter.__extract_data(soup, BS4Adapter.PARSER_PATTERNS["verified"])
        verified = BS4Adapter.__to_boolean_text(verified)

        image = BS4Adapter.__extract_data(soup, BS4Adapter.PARSER_PATTERNS["image"])
        image = BS4Adapter.__to_src(image)

        return {
            "description": description,
            "first_name": first_name,
            "last_name": last_name,
            "__image_url": image,
            "verified": verified,
        }

    @staticmethod
    async def cast_with_avatar(page: str):
        response = BS4Adapter.cast(page)

        image_url = get(response, "image_url")
        if image_url:
            image_base64 = await ImageAdapter.url_to_base64(image_url)
        else:
            image_base64 = None

        return {**response, "image_base64": image_base64}

    @staticmethod
    def __extract_data(soup, condition):
        return soup.select_one(condition)

    @staticmethod
    def __to_str(parse):
        return parse.string if parse else None

    @staticmethod
    def __to_boolean_text(parse):
        return "Да" if bool(parse) else "Нет"

    @staticmethod
    def __to_src(parse):
        return parse["src"] if parse else None
