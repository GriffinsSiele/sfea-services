from typing import Any, Generator, Optional, Sequence


def list_to_chunks(
    lst: Sequence[Any], chunk_size: int = 5
) -> Generator[Sequence[Any], Any, Any]:
    """Split a list into sublists of a specific size."""
    for i in range(0, len(lst), chunk_size):
        yield lst[i : i + chunk_size]


def bounded_value(
    value: int,
    max_: Optional[int] = None,
    min_: Optional[int] = None,
) -> int:
    """Validate and return provided value within boundaries of max and min values"""
    if max_ is not None:
        value = min(max_, value)
    if min_ is not None:
        value = max(min_, value)
    return value
