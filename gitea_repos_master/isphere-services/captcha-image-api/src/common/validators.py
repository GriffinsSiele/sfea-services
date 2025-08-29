from typing import Any, Optional

from sqlalchemy import Column

from src.common import constant, exceptions, utils
from src.db.models import ImageTaskModel, SourceModel


def validate_image(content_type: str | None, file_size: str | int | None):
    if content_type is None or file_size is None:
        raise exceptions.ImageValidationError(
            message=f"Insufficient amount of data for validation procedure."
        )
    if not any(
        [valid_type in content_type for valid_type in constant.FILE_CONTENT_TYPE_LIMIT]
    ):
        raise exceptions.ImageValidationError(
            message=f"Unsupported content type: {content_type}"
        )
    if int(file_size) > constant.FILE_SIZE_LIMIT * 1024 * 1024:
        raise exceptions.ImageValidationError(
            message=f"File size limit {constant.FILE_SIZE_LIMIT}MB exceeded"
        )


class CaptchaSolutionValidator:
    @staticmethod
    def _check_length_invalid(
        spec: Column[dict[str, Any]], solution: str, error_template: str
    ) -> None:
        min_len: Optional[int] = utils.extract_number(spec.get("minLength", None))
        text_len: int = len(solution)
        max_len: Optional[int] = utils.extract_number(spec.get("maxLength", None))

        if min_len and max_len and not min_len <= text_len <= max_len:  # type: ignore[operator]
            raise exceptions.ValidationError(
                f"{error_template} Invalid length: {text_len} ({min_len}..{max_len})"
            )

    @staticmethod
    def _check_symbols_invalid(
        spec: Column[dict[str, Any]], solution: str, error_template: str
    ) -> None:
        _char_pool: str = spec.get("characters", None)
        _case_sensitive: str = spec.get("case", None)
        if _char_pool:
            char_pool = set(_char_pool)
            text_char_pool = set(solution if _case_sensitive else solution.lower())

            if not char_pool.issuperset(text_char_pool):  # type: ignore[union-attr]
                raise exceptions.ValidationError(
                    f"{error_template} Invalid symbols: {', '.join(text_char_pool.difference(char_pool))} ({_char_pool}) (case_sensitive={_case_sensitive})"
                )

    @classmethod
    async def validate_task_solution(
        cls, task: ImageTaskModel, source: SourceModel
    ) -> None:
        if task.solution is not None:
            error_template = (
                f"TASK: {task.id}, SOLUTION: '{task.solution}', Validation error:"
            )

            cls._check_length_invalid(
                spec=source.solution_specification,  # type: ignore[union-attr]
                solution=str(task.solution),
                error_template=error_template,
            )
            cls._check_symbols_invalid(
                spec=source.solution_specification,  # type: ignore[union-attr]
                solution=str(task.solution),
                error_template=error_template,
            )
