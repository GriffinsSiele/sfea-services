from typing import List, Optional
from pydantic import BaseModel, Field


class AggregateRequest(BaseModel):
    """Batch input for unified controller. Accepts mixed data strings."""
    inputs: List[str] = Field(..., description="List of inputs such as phones, emails, usernames")
    source: Optional[str] = Field(default="api", description="Caller identifier")



