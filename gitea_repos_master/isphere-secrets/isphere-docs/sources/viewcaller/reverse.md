---
title: Исследование ViewCaller
description: 
published: true
date: 2024-10-12T18:38:23.647Z
tags: 
editor: markdown
dateCreated: 2024-10-12T18:38:23.647Z
---

# Исследование ViewCaller


Сниппет Frida для перехвата HMAC:

```js
function jhexdump(array) {
    if(!array) return;
    console.log("---------jhexdump start---------");
    var ptr = Memory.alloc(array.length);
    for(var i = 0; i < array.length; ++i)
        Memory.writeS8(ptr.add(i), array[i]);
    console.log(hexdump(ptr, {offset: 0, length: array.length, header: false, ansi: false}));
    console.log("---------jhexdump end---------");
}

function java_hook(){
    Java.perform(function(){
        let SecretKeySpecCls = Java.use("javax.crypto.spec.SecretKeySpec");
        let MacCls = Java.use("javax.crypto.Mac");


        SecretKeySpecCls.$init.overload('[B', 'java.lang.String').implementation = function(key, method){
            console.log("---------enter SecretKeySpec init---------");
            jhexdump(key);
            let ret = this.$init(key, method);
            console.log(key, method, ret);
            return ret;
        }
        MacCls.doFinal.overload('[B').implementation = function(data){
            console.log("---------enter SecretKeySpec init---------");
            jhexdump(data);
            let ret = this.doFinal(data);
            console.log(data, ret);
            return ret;
        }
    })
}

setImmediate(java_hook)
```

Запуск:
```
frida -U -l /home/alien/.config/JetBrains/PyCharm2024.1/scratches/1.js -f id.caller.viewcaller
```

Вывод:
```
---------jhexdump end---------
98,48,52,53,56,99,50,98,50,54,50,57,52,57,98,56 AES undefined
---------enter SecretKeySpec init---------
---------jhexdump start---------
734d58920fe0  5a 3d 48 68 79 60 69 6a 63 2d 37 33 38 67 35 66  Z=Hhy`ijc-738g5f
734d58920ff0  69 3f                                            i?
---------jhexdump end---------
90,61,72,104,121,96,105,106,99,45,55,51,56,103,53,102,105,63 HmacSHA256 undefined
```