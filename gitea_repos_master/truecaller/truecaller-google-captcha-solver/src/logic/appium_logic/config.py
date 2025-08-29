capabilities = dict(
    platformName="Android",
    automationName="uiautomator2",
    deviceName="Android Emulator",
    appPackage="com.truecaller",
    appActivity=".Settings",
    language="en",
    locale="US",
)

APPIUM_CONFIG = {
    "platformName": "Android",
    "platformVersion": "6",
    "deviceName": "Android Emulator",
    "automationName": "UiAutomator2",
    "autoGrantPermissions": True,
    "noReset": True,
}

APPIUM_URL = "http://127.0.0.1:4723/wd/hub"
