import asyncio
import time
from typing import Dict, Any
from datetime import datetime, timedelta


class RateLimiter:
    """Rate limiting middleware for Telegram API"""
    
    def __init__(self, max_requests: int = 100, window_seconds: int = 3600):
        self.max_requests = max_requests
        self.window_seconds = window_seconds
        self.requests: Dict[str, list] = {}
        self.lock = asyncio.Lock()
    
    async def check_rate_limit(self, client_id: str = "default") -> bool:
        """Check if request is within rate limits"""
        async with self.lock:
            now = time.time()
            
            # Initialize client requests if not exists
            if client_id not in self.requests:
                self.requests[client_id] = []
            
            # Remove expired requests
            self.requests[client_id] = [
                req_time for req_time in self.requests[client_id]
                if now - req_time < self.window_seconds
            ]
            
            # Check if limit exceeded
            if len(self.requests[client_id]) >= self.max_requests:
                return False
            
            # Add current request
            self.requests[client_id].append(now)
            return True
    
    async def get_retry_after(self, client_id: str = "default") -> int:
        """Get seconds until rate limit resets"""
        async with self.lock:
            if client_id not in self.requests or not self.requests[client_id]:
                return 0
            
            # Find oldest request in current window
            oldest_request = min(self.requests[client_id])
            now = time.time()
            
            # Calculate time until window resets
            time_elapsed = now - oldest_request
            retry_after = self.window_seconds - time_elapsed
            
            return max(0, int(retry_after))
    
    async def get_current_usage(self, client_id: str = "default") -> Dict[str, Any]:
        """Get current rate limit usage information"""
        async with self.lock:
            now = time.time()
            
            if client_id not in self.requests:
                return {
                    "current_requests": 0,
                    "max_requests": self.max_requests,
                    "window_seconds": self.window_seconds,
                    "reset_time": now + self.window_seconds
                }
            
            # Remove expired requests
            self.requests[client_id] = [
                req_time for req_time in self.requests[client_id]
                if now - req_time < self.window_seconds
            ]
            
            # Find oldest request for reset time calculation
            oldest_request = min(self.requests[client_id]) if self.requests[client_id] else now
            reset_time = oldest_request + self.window_seconds
            
            return {
                "current_requests": len(self.requests[client_id]),
                "max_requests": self.max_requests,
                "window_seconds": self.window_seconds,
                "reset_time": reset_time,
                "remaining_requests": self.max_requests - len(self.requests[client_id])
            }

