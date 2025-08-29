#!/usr/bin/env python3
"""
Simple test runner for the refactored Telegram service
Run this to execute all tests and verify functionality
"""

import subprocess
import sys
import os

def run_command(command, description):
    """Run a command and display results"""
    print(f"\n{'='*60}")
    print(f"🔍 {description}")
    print(f"{'='*60}")
    print(f"Running: {command}")
    print()
    
    try:
        result = subprocess.run(
            command,
            shell=True,
            capture_output=True,
            text=True,
            cwd=os.path.dirname(__file__)
        )
        
        if result.stdout:
            print("✅ STDOUT:")
            print(result.stdout)
        
        if result.stderr:
            print("⚠️  STDERR:")
            print(result.stderr)
        
        if result.returncode == 0:
            print(f"✅ {description} completed successfully")
            return True
        else:
            print(f"❌ {description} failed with return code {result.returncode}")
            return False
            
    except Exception as e:
        print(f"❌ Error running {description}: {e}")
        return False

def main():
    """Run all tests"""
    print("🚀 Starting comprehensive testing for refactored Telegram service...")
    
    # Check if we're in the right directory
    if not os.path.exists("src"):
        print("❌ Error: 'src' directory not found. Please run this script from the telegram-web-api directory.")
        return 1
    
    # Test 1: Basic structure test
    print("\n📋 Test 1: Basic Structure Validation")
    basic_test = run_command(
        "python -X utf8 test_basic.py",
        "Basic structure and import test"
    )
    
    if not basic_test:
        print("❌ Basic test failed. Stopping further tests.")
        return 1
    
    # Test 2: Domain layer tests
    print("\n📋 Test 2: Domain Layer Tests")
    domain_tests = run_command(
        "python -X utf8 -m pytest tests/test_domain.py -v",
        "Domain layer tests (entities, value objects)"
    )
    
    # Test 3: API tests
    print("\n📋 Test 3: API Endpoint Tests")
    api_tests = run_command(
        "python -X utf8 -m pytest tests/test_api.py -v",
        "API endpoint tests"
    )
    
    # Test 4: All tests with coverage
    print("\n📋 Test 4: Full Test Suite with Coverage")
    coverage_tests = run_command(
        "python -X utf8 -m pytest tests/ -k 'not test_telegram' --cov=src --cov-report=term-missing -v",
        "Full test suite with coverage report"
    )
    
    # Test 5: FastAPI startup test
    print("\n📋 Test 5: FastAPI Application Startup Test")
    startup_test = run_command(
        "python -X utf8 -c \"from main import app; print('FastAPI app imported successfully')\"",
        "FastAPI application import test"
    )
    
    # Summary
    print("\n" + "="*60)
    print("📊 TESTING SUMMARY")
    print("="*60)
    
    tests = [
        ("Basic Structure", basic_test),
        ("Domain Layer", domain_tests),
        ("API Endpoints", api_tests),
        ("Full Coverage", coverage_tests),
        ("FastAPI Startup", startup_test)
    ]
    
    passed = sum(1 for _, result in tests if result)
    total = len(tests)
    
    for name, result in tests:
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{name:20s} - {status}")
    
    print(f"\nOverall: {passed}/{total} test categories passed")
    
    if passed == total:
        print("\n🎉 All tests passed! The refactored service is working correctly.")
        print("\n🚀 Next steps:")
        print("1. Start the service: python main.py")
        print("2. Test with real API calls")
        print("3. Deploy to your environment")
        return 0
    else:
        print(f"\n⚠️  {total - passed} test categories failed. Please check the errors above.")
        return 1

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)
