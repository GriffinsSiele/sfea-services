import asyncio
from math import ceil
from typing import Optional, Sequence

from livenessprobe_logic import HealthCheck
from sqlalchemy.ext.asyncio import AsyncSession

from daemon.repository import TokenDaemonRepository
from daemon.utils import bounded_value, list_to_chunks
from src.common import utils
from src.config.daemon_config import daemon_settings
from src.config.provider_config import provider_settings
from src.db.models import TokenTaskModel, WebsiteModel
from src.db.session import session_generator
from src.logic.provider_api import provider_api_service
from src.logic.websites import website_service


class TokenDaemon(utils.SingletonLogging):
    def __init__(self):
        super().__init__()
        self.repository = TokenDaemonRepository()
        self.exceptions_was_met = False
        self.callback_url_tamplate = (
            provider_settings.CALLBACK_URL + "?task_id={task_id}&task_type={task_type}"
        )

    async def _get_websites(self) -> Sequence[WebsiteModel]:
        try:
            async with session_generator() as db:
                return await website_service.get_websites(db=db)
        except Exception as exc:
            self.exceptions_was_met = True
            self.logger.info(
                f"An error occurred while trying to get websites data: {type(exc)} {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            )
            return []

    async def _define_pool_and_tokens_to_request(
        self, db: AsyncSession, website: WebsiteModel
    ) -> tuple[int, int]:
        extra_token_factor = website.website_config.get("extra_token_factor", 1)
        (
            used_tokens,
            reserved_tokens,
        ) = await self.repository.count_avg_token_usage_for_website(
            db=db, website=website
        )

        _tokens_to_request = used_tokens - reserved_tokens

        tokens_to_request = bounded_value(
            max_=int(website.max_token_pool) - reserved_tokens,
            min_=ceil(
                max(0, int(website.min_token_pool) - reserved_tokens) * extra_token_factor
            ),
            value=ceil(_tokens_to_request * extra_token_factor),
        )

        token_pool = bounded_value(
            max_=int(website.max_token_pool),
            min_=int(website.min_token_pool),
            value=(reserved_tokens + tokens_to_request),
        )
        return token_pool, tokens_to_request

    async def _submit_token_task(
        self, task: TokenTaskModel, website: WebsiteModel
    ) -> Optional[str]:
        try:
            captcha_id = await provider_api_service.submit_token_task(
                provider=website.website_config["provider"],  # type: ignore[arg-type]
                callback_url=self.callback_url_tamplate.format(
                    task_id=task.id, task_type=task.task_type
                ),
                website_data=website.dict(),
            )
            return str(captcha_id)
        except Exception as exc:
            self.exceptions_was_met = True
            self.logger.info(
                f"An error occurred while trying to get websites data: {type(exc)} {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            )
            return None

    async def _request_new_tokens(
        self, db: AsyncSession, website: WebsiteModel, amount: int
    ) -> None:
        if amount > 0:
            self.logger.info(f"{website.name}: Requesting {amount} tokens")
            tasks = await self.repository.create_task_shells(
                db=db, website=website, amount=amount
            )

            for tasks_sublist in list_to_chunks(tasks):
                captcha_ids = await asyncio.gather(
                    *[self._submit_token_task(task, website) for task in tasks_sublist]
                )
                await self.repository.update_task_shells(
                    db=db, tasks=tasks_sublist, captcha_ids=captcha_ids
                )

    async def survey_token_pool(self, website: WebsiteModel) -> None:
        try:
            async with session_generator() as db:
                db.add(website)
                (
                    new_token_pool,
                    tokens_to_request,
                ) = await self._define_pool_and_tokens_to_request(db=db, website=website)

                updated_website = await self.repository.update_token_pool(
                    db=db, website=website, pool=new_token_pool
                )
                await self._request_new_tokens(
                    db=db, website=updated_website, amount=tokens_to_request
                )
        except Exception as exc:
            self.exceptions_was_met = True
            self.logger.info(
                f"An error occurred while surveying {website.name} website's pool: {type(exc)} {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            )

    async def start(self) -> None:
        websites = await self._get_websites()
        website_sublists = list_to_chunks(websites)
        for sublist in website_sublists:
            await asyncio.gather(
                *[self.survey_token_pool(website) for website in sublist]
            )

    def healthcheck(self) -> None:
        if not self.exceptions_was_met:
            self.logger.info("CHECKPOINT")
            HealthCheck().checkpoint()
        self.exceptions_was_met = False


async def main() -> None:
    token_daemon = TokenDaemon()
    while True:
        await token_daemon.start()
        token_daemon.healthcheck()
        await asyncio.sleep(daemon_settings.SURVEY_CYCLE)
        token_daemon.logger.info(
            f"Resume pool observing after {daemon_settings.SURVEY_CYCLE} seconds..."
        )


if __name__ == "__main__":
    asyncio.run(main())
