# Запуск перехвата трафика приложения

Unpinning работает на Android 10.0 (API 29), на Android 6.0 не работает. 

1. Запустить BurpSuite с proxy "All interfaces", port=8080, invisible proxy=true
2. На Android в WiFi указать Proxy 192.168.1.87:8080 (192.168.1.87 - IP Local of PC)
3. Запуск frida сервер
```shell
cd /opt/genymobile/genymotion/tools/
./adb shell
su
cd /data/local/tmp/
./frida-server
```
4. Новый терминал, запуск unpinning
```shell
./adb shell monkey -p com.numbuster.android 1; frida -U Numbuster! -l $HOME/Desktop/git/i-sphere/numbuster-api/frida/unpinning.js
```

