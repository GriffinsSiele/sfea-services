from io import BytesIO
from typing import Any

import numpy as np
import onnxruntime
from PIL import Image, UnidentifiedImageError

from src.common.exceptions import BadRequestException
from src.common.utils import format_float
from src.config.api_config import api_settings


class NNetworkDecoder:
    def __init__(self, nnetwork_data: dict, color: bool = True):
        self.color = color
        self.params = nnetwork_data["network_params"]
        self.session = onnxruntime.InferenceSession(
            path_or_bytes=f"{api_settings.NNETWORKS_LOCAL_STORE_PATH}/{nnetwork_data['networkname']}",
            providers=["AzureExecutionProvider", "CPUExecutionProvider"],
        )

    def get_result(self, pred: "np.ndarray") -> dict[str, Any]:
        accuracy = 1
        last = None
        char_list = []
        # pred - 3d tensor, we need 2d array - first element
        for item in pred[0]:
            # get index of element with max accuracy
            char_ind = item.argmax()
            # ignore duplicates and special characters
            if char_ind not in {0, last, len(self.params["characters"]) + 1}:
                char_list.append(self.params["characters"][char_ind - 1])
                accuracy *= item[char_ind]
            last = char_ind

        decoded_text = "".join(char_list)[: int(self.params["answer_lenght"])]
        return {"solution": decoded_text, "accuracy": format_float(accuracy)}

    def process_image(self, buffer) -> "np.ndarray":
        convert_type = "RGB" if self.color else "L"
        try:
            img: "Image.Image" = Image.open(BytesIO(buffer)).convert(convert_type)
        except UnidentifiedImageError:
            raise BadRequestException("Unable to identify provided image")
        img_ndarray: "np.ndarray" = np.array(
            img.resize(
                (
                    int(self.params["img_width"]),
                    int(self.params["img_heigh"]),
                )
            )
        )
        img_ndarray = img_ndarray.astype(np.float32) / 255.0
        img_ndarray = np.rollaxis(img_ndarray, axis=1, start=0)
        img_ndarray = np.expand_dims(img_ndarray, axis=0)
        return img_ndarray

    def solve(self, file: bytes) -> dict[str, Any]:
        img = self.process_image(file)
        name: str = self.session.get_inputs()[0].name
        pred_onx: "np.ndarray" = self.session.run(None, {name: img})[0]
        return self.get_result(pred_onx)
