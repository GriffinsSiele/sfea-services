var hmac = {};
hmac.states = [[209996835, -1146438089, 984317253, 247523629, -1738066779, -1503442420, 38053859, -155764797], [-672755480, 502346353, -891606301, -509839519, -1999418273, 391933883, 1491520601, 1999968729]],
hmac.blockSize = 16,
hmac.hash = function(e, t) {
    switch ("object" != typeof t && (t = {}),
    t.encoding) {
    case "hex":
        e = hmac.h2s(e);
        break;
    default:
        e = hmac.utf8Encode(e)
    }
    var r = hmac.prepareMessage(e)
      , i = hmac.states[0].slice()
      , s = hmac.hashBytes(r, i);
    r = hmac.prepareMessage(hmac.a2s(s)),
    i = hmac.states[1].slice(),
    s = hmac.hashBytes(r, i);
    var n = "";
    switch (t.encoding) {
    case "hex":
        n = hmac.a2h(s);
        break;
    case "binary":
        n = hmac.a2s(s);
        break;
    default:
        n = hmac.a2h(s)
    }
    return n
}
,
hmac.prepareMessage = function(e) {
    e += String.fromCharCode(128);
    var t, r, i = e.length / 4 + 2, s = Math.ceil(i / hmac.blockSize), n = [];
    for (t = 0; t < s; t++)
        for (n[t] = [],
        r = 0; r < hmac.blockSize; r++)
            n[t][r] = e.charCodeAt(64 * t + 4 * r) << 24 | e.charCodeAt(64 * t + 4 * r + 1) << 16 | e.charCodeAt(64 * t + 4 * r + 2) << 8 | e.charCodeAt(64 * t + 4 * r + 3);
    var a = e.length + 4 * hmac.blockSize - 1;
    return n[s - 1][hmac.blockSize - 2] = 8 * a / Math.pow(2, 32),
    n[s - 1][hmac.blockSize - 2] = Math.floor(n[s - 1][hmac.blockSize - 2]),
    n[s - 1][hmac.blockSize - 1] = 8 * a & 4294967295,
    n
}
,
hmac.hashBytes = function(e, t) {
    var r, i, s, n, a, o, c, u, l, p, h = [], d = e.length;
    for (l = 0; l < d; l++) {
        for (p = 0; p < hmac.blockSize; p++)
            h[p] = e[l][p];
        for (p = hmac.blockSize; p < 64; p++)
            h[p] = hmac.t1(h[p - 2]) + h[p - 7] + hmac.t0(h[p - 15]) + h[p - 16],
            h[p] &= 4294967295;
        for (r = t[0],
        i = t[1],
        s = t[2],
        n = t[3],
        a = t[4],
        o = t[5],
        c = t[6],
        u = t[7],
        p = 0; p < 64; p++) {
            var f = u + hmac.s1(a) + hmac.ch(a, o, c) + hmac.K[p] + h[p]
              , m = hmac.s0(r) + hmac.maj(r, i, s);
            u = c,
            c = o,
            o = a,
            a = n + f & 4294967295,
            n = s,
            s = i,
            i = r,
            r = f + m & 4294967295
        }
        t[0] = t[0] + r & 4294967295,
        t[1] = t[1] + i & 4294967295,
        t[2] = t[2] + s & 4294967295,
        t[3] = t[3] + n & 4294967295,
        t[4] = t[4] + a & 4294967295,
        t[5] = t[5] + o & 4294967295,
        t[6] = t[6] + c & 4294967295,
        t[7] = t[7] + u & 4294967295
    }
    return t
}
,
hmac.rotr = function(e, t) {
    return t >>> e | t << 32 - e
}
,
hmac.s0 = function(e) {
    return hmac.rotr(2, e) ^ hmac.rotr(13, e) ^ hmac.rotr(22, e)
}
,
hmac.s1 = function(e) {
    return hmac.rotr(6, e) ^ hmac.rotr(11, e) ^ hmac.rotr(25, e)
}
,
hmac.t0 = function(e) {
    return hmac.rotr(7, e) ^ hmac.rotr(18, e) ^ e >>> 3
}
,
hmac.t1 = function(e) {
    return hmac.rotr(17, e) ^ hmac.rotr(19, e) ^ e >>> 10
}
,
hmac.ch = function(e, t, r) {
    return e & t ^ ~e & r
}
,
hmac.maj = function(e, t, r) {
    return e & t ^ e & r ^ t & r
}
,
hmac.K = [1116352408, 1899447441, 3049323471, 3921009573, 961987163, 1508970993, 2453635748, 2870763221, 3624381080, 310598401, 607225278, 1426881987, 1925078388, 2162078206, 2614888103, 3248222580, 3835390401, 4022224774, 264347078, 604807628, 770255983, 1249150122, 1555081692, 1996064986, 2554220882, 2821834349, 2952996808, 3210313671, 3336571891, 3584528711, 113926993, 338241895, 666307205, 773529912, 1294757372, 1396182291, 1695183700, 1986661051, 2177026350, 2456956037, 2730485921, 2820302411, 3259730800, 3345764771, 3516065817, 3600352804, 4094571909, 275423344, 430227734, 506948616, 659060556, 883997877, 958139571, 1322822218, 1537002063, 1747873779, 1955562222, 2024104815, 2227730452, 2361852424, 2428436474, 2756734187, 3204031479, 3329325298],
hmac.utf8Encode = function(e) {
    try {
        var t, r, i, s, n = "", a = [];
        for (t = 0,
        i = e.length; t < i; t++)
            r = e.charCodeAt(t),
            r < 128 ? a.push(r) : r < 2048 ? a.push(192 | r >> 6, 128 | 63 & r) : r < 55296 || r >= 57344 ? a.push(224 | r >> 12, 128 | r >> 6 & 63, 128 | 63 & r) : (t++,
            r = 65536 + ((1023 & r) << 10 | 1023 & e.charCodeAt(t)),
            a.push(240 | r >> 18, 128 | r >> 12 & 63, 128 | r >> 6 & 63, 128 | 63 & r));
        for (t = 0,
        s = a.length; t < s; t++)
            n += String.fromCharCode(a[t]);
        return n
    } catch (e) {
        throw new Error("Error on UTF-8 encode")
    }
}
,
hmac.i2b = function(e) {
    var t, r = [];
    for (t = 3; t >= 0; t--)
        r[3 - t] = e >>> 8 * t & 255;
    return r
}
,
hmac.a2s = function(e) {
    var t, r, i, s, n, a = "";
    for (t = 0,
    i = e.length; t < i; t++)
        for (s = hmac.i2b(e[t]),
        r = 0,
        n = s.length; r < n; r++)
            a += String.fromCharCode(s[r]);
    return a
}
,
hmac.a2h = function(e) {
    var t, r, i, s, n, a = "";
    for (t = 0,
    s = e.length; t < s; t++)
        for (r = hmac.i2b(e[t]),
        i = 0,
        n = r.length; i < n; i++)
            a += (r[i] < 16 ? "0" : "") + r[i].toString(16);
    return a
}
,
hmac.h2s = function(e) {
    if (0 === e.length)
        return "";
    if (e.length % 2 === 1)
        throw Error("Odd-length string");
    var t, r = "", i = e.match(/[0-9a-f]{2}/gi), s = i.length;
    for (t = 0; t < s; t++)
        r += String.fromCharCode(parseInt(i[t], 16));
    return r
}


console.log(hmac.hash(process.argv[2]))
