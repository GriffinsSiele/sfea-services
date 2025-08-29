from typing import Any, Callable, Dict, List, Tuple

from numpy import ndarray

from fastapi import UploadFile

ImgShape = Tuple[int, int]

ValidateList = List[UploadFile]

UploadImage = Tuple[str, ndarray, ImgShape]

UploadPolicyImage = UploadImage | None

LineCoord = List[int]

SimilarityKey = int | None

# Список изображений
ListImages = List[ndarray]

FindPolicies = Tuple[ListImages, ListImages]

RecognizeHeader = Dict[int, str]

UnrecognizedHeader = Dict[str, str]

RecognizeBody = Dict[int, List[str]]

PolicyRecognizeHeader = Dict[int, str]

PolicyRecognizeBody = Dict[int, str]

PolicyAsociateBody = Dict[int, str]

RecognizePolicy = Dict[str, str]

RecognizeResult = Dict[str, List[str]]

JsonList = List[Dict[str, str]]

# Словарь изображений-заголовков, находящихся по координатам столбца
ImagePosition = Dict[int, ndarray]

# Словарь изображений-содержания, находящихся по координатам столбца
BodyPosition = Dict[int, List[ndarray]]

# Словарь изображений стобца и значений для карточки страхового полиса, находящихся по координатам столбца
PolicyPosition = Dict[int, List[ndarray]]

# Координаты прямоугольников, в формате [(y0,y1),(x0,x1)]
RectCoord = List[List[Tuple[int, int]]]

InsideTable = Tuple[int, int]

HeadResult = Tuple[RecognizeHeader, UnrecognizedHeader]

ParsingInside = Tuple[ImagePosition, BodyPosition]

FiltredValues = Tuple[ImagePosition, BodyPosition, int]

HeaderConfig = Tuple[str, Callable[[ListImages, int], str]]
BodyConfig = Tuple[Dict[int, str], Callable[[str, str], str]]

InsideValues = List[Dict[int, ndarray]]
InsideAssociate = List[Dict[int, str]]

InsideCombining = List[Dict[str, str]]

ReplacementInside = Tuple[ImagePosition, BodyPosition, InsideValues, InsideValues]

PreprocessImages = Dict[str, Dict]

AssociateResult = Dict[str, List]

ValidationMain = List[Dict[str, Any]]

ValidationPolicy = Dict[str, str]

ValidationError = List[str]

ResponseAnswer = Dict[str, ValidationMain | ValidationPolicy | ValidationError]
