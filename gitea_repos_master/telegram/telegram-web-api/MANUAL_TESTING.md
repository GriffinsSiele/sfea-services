# Manual Testing Guide

This guide provides step-by-step instructions for manually testing the refactored Telegram service to ensure it's working as required.

## 🚀 **Quick Start Testing**

### **Step 1: Basic Structure Test**
```bash
# Navigate to the service directory
cd telegram-web-api

# Run the basic structure test
python test_basic.py
```

**Expected Result**: All imports should succeed, and you should see "✅ All tests passed!"

### **Step 2: Start the Service**
```bash
# Install dependencies (if not already done)
pip install -r requirements.txt

# Start the FastAPI service
python main.py
```

**Expected Result**: Service should start on `http://localhost:8000` with no errors.

### **Step 3: Test Health Endpoints**
Open your browser or use curl:

```bash
# Test root endpoint
curl http://localhost:8000/

# Test health endpoint
curl http://localhost:8000/health

# Test Telegram health endpoint
curl http://localhost:8000/api/v1/telegram/health
```

**Expected Results**:
- Root: Service information and available endpoints
- Health: `{"status": "healthy", "service": "telegram-api"}`
- Telegram Health: Same as general health

## 📡 **API Endpoint Testing**

### **Test 1: Phone Number Search**
```bash
# Test phone search with valid number
curl -X POST http://localhost:8000/api/v1/telegram/search \
  -H "Content-Type: application/json" \
  -d '{"phone": "79319999999"}'
```

**Expected Result**: 
- Status: 200 OK
- Response should contain standardized format with `success`, `data`, `errors`, and `metadata`
- Since this is a test environment, you might get an error about no available sessions (which is expected)

### **Test 2: Username Search**
```bash
# Test username search
curl -X POST http://localhost:8000/api/v1/telegram/search \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser"}'
```

**Expected Result**: Same standardized response format

### **Test 3: Validation Errors**
```bash
# Test both parameters (should fail)
curl -X POST http://localhost:8000/api/v1/telegram/search \
  -H "Content-Type: application/json" \
  -d '{"phone": "79319999999", "username": "testuser"}'
```

**Expected Result**: 
- Status: 200 OK
- `"success": false`
- `"error_code": "VALIDATION_ERROR"`
- Clear error message about mutual exclusion

```bash
# Test no parameters (should fail)
curl -X POST http://localhost:8000/api/v1/telegram/search \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Expected Result**: Validation error about missing parameters

```bash
# Test invalid phone format
curl -X POST http://localhost:8000/api/v1/telegram/search \
  -H "Content-Type: application/json" \
  -d '{"phone": "invalid_phone"}'
```

**Expected Result**: Validation error about invalid phone format

```bash
# Test invalid username format
curl -X POST http://localhost:8000/api/v1/telegram/search \
  -H "Content-Type: application/json" \
  -d '{"username": "ab"}'
```

**Expected Result**: Validation error about username being too short

## 🔍 **Response Format Validation**

### **Successful Response Structure**
```json
{
  "success": true,
  "data": [
    {
      "id": 123456789,
      "username": "testuser",
      "first_name": "Test",
      "last_name": "User",
      "phone": "79319999999",
      "is_bot": false,
      "is_verified": false,
      "is_restricted": false,
      "is_scam": false,
      "is_fake": false,
      "access_hash": 12345678901234567890,
      "photo": null,
      "status": "online",
      "created_at": "2024-01-01T00:00:00",
      "updated_at": "2024-01-01T00:00:00"
    }
  ],
  "errors": [],
  "metadata": {
    "search_type": "phone",
    "query": "79319999999",
    "results_count": 1,
    "session_id": "session_123"
  }
}
```

### **Error Response Structure**
```json
{
  "success": false,
  "error": "Error message",
  "error_code": "ERROR_CODE",
  "details": {
    "additional": "information"
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

## 🧪 **Comprehensive Testing**

### **Run All Automated Tests**
```bash
# Run the test runner
python run_tests.py

# Or run pytest directly
python -m pytest tests/ -v

# With coverage
python -m pytest tests/ --cov=src --cov-report=term-missing -v
```

### **Test Rate Limiting**
```bash
# Make multiple rapid requests to test rate limiting
for i in {1..110}; do
  curl -X POST http://localhost:8000/api/v1/telegram/search \
    -H "Content-Type: application/json" \
    -d '{"phone": "79319999999"}' &
done
wait
```

**Expected Result**: After 100 requests (default limit), you should get rate limit errors.

## 🔧 **Configuration Testing**

### **Environment Variables**
Create a `.env` file for testing:

```env
MODE=development
PORT=8000
MONGO_URL=mongodb://localhost:27017
MONGO_DB=telegram_api_test
KEYDB_URL=redis://localhost:6379
TELEGRAM_API_ID=your_test_api_id
TELEGRAM_API_HASH=your_test_api_hash
MAX_REQUESTS_PER_HOUR=50
```

### **Test Different Modes**
```bash
# Test development mode
MODE=development python main.py

# Test production mode (should disable docs)
MODE=production python main.py
```

## 📊 **Performance Testing**

### **Load Testing**
```bash
# Install Apache Bench if available
ab -n 1000 -c 10 -p test_data.json -T application/json http://localhost:8000/api/v1/telegram/search

# Or use simple load testing
for i in {1..100}; do
  curl -X POST http://localhost:8000/api/v1/telegram/search \
    -H "Content-Type: application/json" \
    -d '{"phone": "79319999999"}' &
done
wait
```

## 🐳 **Docker Testing**

### **Build and Run Container**
```bash
# Build the Docker image
docker build -t telegram-api .

# Run the container
docker run -p 8000:8000 --env-file .env telegram-api

# Test from host
curl http://localhost:8000/health
```

### **Test Health Check**
```bash
# Check container health
docker ps
# Should show "healthy" status after a few seconds
```

## ✅ **Success Criteria Checklist**

- [ ] **Basic Structure**: All imports work without errors
- [ ] **Service Startup**: FastAPI service starts without errors
- [ ] **Health Endpoints**: All health checks return healthy status
- [ ] **API Validation**: Proper validation errors for invalid inputs
- [ ] **Response Format**: All responses follow standardized format
- [ ] **Error Handling**: Proper error codes and messages
- [ ] **Rate Limiting**: Rate limiting works as expected
- [ ] **Documentation**: API docs available at `/docs` (development mode)
- [ ] **Docker**: Container builds and runs successfully
- [ ] **Tests**: All automated tests pass

## 🚨 **Common Issues and Solutions**

### **Import Errors**
- **Issue**: Module import errors
- **Solution**: Ensure you're in the correct directory and `src/` folder exists

### **Port Already in Use**
- **Issue**: Port 8000 already in use
- **Solution**: Change port in `.env` file or kill existing process

### **Database Connection Errors**
- **Issue**: MongoDB/Redis connection failures
- **Solution**: Ensure databases are running or use mock services for testing

### **Telegram API Errors**
- **Issue**: Telegram API authentication failures
- **Solution**: Use valid API credentials or mock the Telegram client for testing

## 🎯 **Next Steps After Testing**

1. **Integration Testing**: Test with real Telegram API credentials
2. **Performance Testing**: Load test with realistic traffic patterns
3. **Security Testing**: Validate input sanitization and rate limiting
4. **Deployment Testing**: Test in staging environment
5. **Monitoring**: Verify health checks and logging work correctly

## 📞 **Getting Help**

If you encounter issues during testing:

1. Check the logs for detailed error messages
2. Verify all dependencies are installed correctly
3. Ensure the service is running on the expected port
4. Check that all required environment variables are set
5. Review the automated test output for specific failures

The refactored service should provide clear, actionable error messages to help with troubleshooting.

