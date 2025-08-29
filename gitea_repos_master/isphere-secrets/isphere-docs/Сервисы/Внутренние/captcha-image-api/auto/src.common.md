# src.common package

## Submodules

## src.common.constants module

### *class* src.common.constants.Constant

Базовые классы: `object`

#### SERVER_STATUS_OK *: `str`* *= 'ok'*

#### SERVER_STATUS_PENDING *: `str`* *= 'pending_nn'*

#### FILE_SIZE_LIMIT *: `int`* *= 10*

#### FILE_CONTENT_TYPE_LIMIT *: `set`[`str`]* *= {'image/jpeg', 'image/png'}*

#### DEFAULT_SOLUTION_SPECIFICATION *: `dict`[`str`, `Union`[`bool`, `int`, `str`]]* *= {'case': True, 'characters': '', 'languagePool': 'ru', 'math': False, 'maxLength': 20, 'minLength': 2, 'numeric': 0, 'phrase': False}*

#### DEFAULT_AUTO_MODE_CONFIG *: `dict`[`str`, `Any`]* *= {'captcha_ttl': 180.0, 'min_acc': 0.0, 'provider_priority': {'antigate': 1, 'capmonster': 2, 'rucaptcha': 3}}*

#### NNETWORKS_PROVIDER *: `str`* *= 'nnetworks'*

#### AUTO_PROVIDER *: `str`* *= 'auto'*

#### SEND_REPORT_DISABLED *: `set`[`str`]* *= {'capmonster-local', 'nnetworks'}*

#### ANTIGATE_MIN_SCORE_VALIDS *: `set`[`float`]* *= {0.3, 0.5, 0.7, 0.9}*

## src.common.deps module

## src.common.enums module

## src.common.exceptions module

## src.common.logger module

## src.common.utils module

## src.common.validators module

## Module contents
