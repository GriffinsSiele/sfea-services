import logging

from src.config import ConfigApp
from src.request_params.interfaces.elpts_signed import SignedParams


class ElPtsPostData(SignedParams):
    def __init__(
        self,
        data_to_send: str,
        form_link_index: str,
        input_id: str,
        csrf_token: str,
        session_id: str,
        url_suffix: str,
        *args,
        **kwargs,
    ):
        self.data = {
            input_id: "",
            "csrftoken": csrf_token,
            "search": "1",
        }
        if url_suffix == "vin":
            self.data["identificationNumber"] = data_to_send
        if url_suffix == "number":
            self.data["passportNumber"] = data_to_send
        if url_suffix not in ["vin", "number"]:
            logging.error('"vin" or "number" is not defined')
        super().__init__(
            url=ConfigApp.MAIN_PAGE_URL + f"?{form_link_index}.IBehaviorListener."
            f"0-servicesPanel-passportSearchPanel-{url_suffix}SearchPanel-searchForm-search",
            cookies={
                "csrf-token-name": "csrftoken",
                "csrf-token-value": csrf_token,
                "JSESSIONID": session_id,
            },
            data=self.data,
            *args,
            **kwargs,
        )
        self.data_to_send = data_to_send
        self.form_link_index = form_link_index
        self.input_id = input_id
        self.csrf_token = csrf_token
