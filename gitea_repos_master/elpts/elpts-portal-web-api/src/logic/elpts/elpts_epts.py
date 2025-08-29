from src.logic.elpts.elpts_vin import ElPtsVin


class ElPtsEpts(ElPtsVin):
    def _parse_main_page(self, html: str) -> dict:
        return self.main_page_parser.set_page(html).parse_epts()

    async def _post_data(
        self, data: str, main_page: dict, session_id: str, url_suffix: str = "number"
    ) -> dict:
        return await super()._post_data(data, main_page, session_id, url_suffix)
