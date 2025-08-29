from fastapi import APIRouter, Depends
from sqlalchemy.ext.asyncio import AsyncSession
from starlette import status

from src.common import constant, deps
from src.common.utils import ImageObject
from src.logic.solvers import image_solver
from src.request_params.input_queries import DecoderInputQuery
from src.schemas import DecoderTaskOutput

router: APIRouter = APIRouter()


@router.post(
    "/image",
    status_code=status.HTTP_200_OK,
    response_model=DecoderTaskOutput,
    description=f"""Раскодирование капчи путем предоставления её в формате файла.
        Доступные форматы: {', '.join(constant.FILE_CONTENT_TYPE_LIMIT)}, максимальный размер: {constant.FILE_SIZE_LIMIT}Мб.""",
)
async def decode_captcha_image_file(
    db: AsyncSession = Depends(deps.get_session),
    *,
    input_data: DecoderInputQuery = Depends(DecoderInputQuery),
    image: ImageObject = Depends(deps.validate_upload_image_and_process_bytes),
) -> DecoderTaskOutput:
    decoder_data = await image_solver.process_captcha_task(
        db=db,
        image=image,
        provider=input_data.provider,
        source=input_data.source,
        timeout=input_data.timeout,
    )
    return DecoderTaskOutput(**decoder_data.model_dump())


@router.post(
    "/url",
    status_code=status.HTTP_200_OK,
    response_model=DecoderTaskOutput,
    description=f"""Раскодирование капчи путем предоставления её URL-адреса.
        Доступные форматы: {', '.join(constant.FILE_CONTENT_TYPE_LIMIT)}, максимальный размер: {constant.FILE_SIZE_LIMIT}Мб.""",
)
async def decode_captcha_image_url(
    db: AsyncSession = Depends(deps.get_session),
    *,
    input_data: DecoderInputQuery = Depends(DecoderInputQuery),
    image: ImageObject = Depends(deps.validate_url_image_and_process_bytes),
) -> DecoderTaskOutput:
    decoder_data = await image_solver.process_captcha_task(
        db=db,
        image=image,
        provider=input_data.provider,
        source=input_data.source,
        timeout=input_data.timeout,
    )
    return DecoderTaskOutput(**decoder_data.model_dump())
