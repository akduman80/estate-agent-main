import phone from '../../vendor/libphonenumber/libphonenumber-max.js';

const units = {sıfır: 0, bir: 1, iki: 2, üç: 3, dört: 4, beş: 5, altı: 6, yedi: 7, sekiz: 8, dokuz: 9};
const tens = {on: 10, yirmi: 20, otuz: 30, kırk: 40, elli: 50, altmış: 60, yetmiş: 70, seksen: 80, doksan: 90};
const months = ['ocak', 'şubat', 'mart', 'nisan', 'mayıs', 'haziran', 'temmuz', 'ağustos', 'eylül', 'ekim', 'kasım', 'aralık'];
const lower = value => value.normalize('NFC').toLocaleLowerCase('tr-TR').trim();
export const digits = value => String(value).replace(/[٠-٩۰-۹]/g, digit => String(digit.charCodeAt(0) % 16));
export const countries = phone.getCountries();
export const callingCode = country => phone.getCountryCallingCode(country);

export function spokenDigits(value) {
    const tokens = lower(digits(value)).replace(/artı/g, '+').replace(/eksi|tire/g, '-').replace(/nokta|virgül/g, ' ')
        .replace(/([+\-])/g, ' $1 ').split(/[\s,().]+/).filter(Boolean);
    let result = '';
    for (let index = 0; index < tokens.length; index++) {
        const token = tokens[index];
        if (/^[+\-\d]+$/.test(token)) { result += token; continue; }
        if (token === 'yüz' || (units[token] !== undefined && tokens[index + 1] === 'yüz')) {
            let number = token === 'yüz' ? 100 : units[token] * 100;
            if (token !== 'yüz') index++;
            if (tens[tokens[index + 1]]) number += tens[tokens[++index]];
            if (units[tokens[index + 1]] !== undefined && tokens[index + 2] !== 'yüz') number += units[tokens[++index]];
            result += number; continue;
        }
        if (tens[token]) {
            let number = tens[token];
            if (units[tokens[index + 1]] !== undefined && tokens[index + 2] !== 'yüz') number += units[tokens[++index]];
            result += number; continue;
        }
        if (units[token] !== undefined) { result += units[token]; continue; }
        return null; // Do not guess unknown spoken words in identity/phone fields.
    }
    return result;
}

export function turkishInteger(value) {
    const tokens = lower(value).split(/\s+/).filter(Boolean);
    if (/^\d+$/.test(value.trim())) return value.trim();
    let total = 0, group = 0;
    for (const token of tokens) {
        if (units[token] !== undefined) group += units[token];
        else if (tens[token]) group += tens[token];
        else if (token === 'yüz') group = (group || 1) * 100;
        else if (['bin', 'milyon', 'milyar'].includes(token)) {
            total += (group || 1) * {bin: 1000, milyon: 1000000, milyar: 1000000000}[token]; group = 0;
        } else return null;
    }
    return tokens.length && Number.isSafeInteger(total + group) ? String(total + group) : null;
}

export function normalizeSpeech(value, kind) {
    if (['identity', 'phone'].includes(kind)) {
        const source = kind === 'phone' ? lower(value).replace(/^artı\s+doksan\s+/, 'artı 90 ') : value;
        return spokenDigits(source) ?? value;
    }
    if (kind === 'email') return lower(value).replace(/\s*(?:et işareti|kuyruklu a|at işareti)\s*/g, '@').replace(/\s*nokta\s*/g, '.').replace(/\s+/g, '');
    if (kind === 'date') {
        const text = lower(value);
        for (const [index, month] of months.entries()) {
            const parts = text.split(new RegExp(`\\s+${month}\\s+`));
            if (parts.length === 2) {
                const day = turkishInteger(parts[0]), year = turkishInteger(parts[1]);
                if (day && year) return `${day.padStart(2, '0')}.${String(index + 1).padStart(2, '0')}.${year}`;
            }
        }
        const parts = text.split(/\s*(?:nokta|bölü|\/|\.)\s*/);
        if (parts.length === 3) { const numbers = parts.map(turkishInteger); if (numbers.every(Boolean)) return numbers.join('.'); }
    }
    if (kind === 'amount') {
        const money = lower(value).match(/^(.+?)\s+(?:türk lirası|lira|dolar|avro|euro|sterlin)\s+(.+?)\s+kuruş$/);
        if (money) {
            const main = turkishInteger(money[1]), fraction = turkishInteger(money[2]);
            if (main !== null && fraction !== null && +fraction < 100) return `${main},${fraction.padStart(2, '0')}`;
        }
        const text = lower(value).replace(/(?:türk lirası|lira|dolar|avro|euro|sterlin)/g, ' ').trim();
        const kuru = text.match(/^(.*?)\s+(.+?)\s+kuruş$/);
        if (kuru) { const main = turkishInteger(kuru[1]), fraction = turkishInteger(kuru[2]); if (main && fraction && +fraction < 100) return `${main},${fraction.padStart(2, '0')}`; }
        const parts = text.split(/\s+virgül\s+/);
        const main = turkishInteger(parts[0]);
        if (main && parts.length === 1) return main;
        if (main && parts.length === 2) { const fraction = turkishInteger(parts[1]); if (fraction !== null) return `${main},${fraction}`; }
    }
    return value;
}

export function validIdentity(value) {
    if (!/^[1-9]\d{10}$/.test(value)) return false;
    const n = [...value].map(Number);
    const odd = n[0] + n[2] + n[4] + n[6] + n[8], even = n[1] + n[3] + n[5] + n[7];
    return ((odd * 7 - even) % 10 + 10) % 10 === n[9] && n.slice(0, 10).reduce((a, b) => a + b, 0) % 10 === n[10];
}

export function dateValue(value) {
    const text = digits(value).trim();
    let match = text.match(/^(\d{4})-(\d{2})-(\d{2})$/), year, month, day;
    if (match) [, year, month, day] = match;
    else { match = text.match(/^(\d{1,2})[.\/\s-](\d{1,2})[.\/\s-](\d{4})$/); if (!match) return null; [, day, month, year] = match; }
    const d = Number(day), m = Number(month), y = Number(year);
    if (y < 1 || y > 9999 || m < 1 || m > 12 || d < 1) return null;
    const leap = y % 4 === 0 && (y % 100 !== 0 || y % 400 === 0);
    if (d > [31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][m - 1]) return null;
    return {canonical: `${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`, display: `${day.padStart(2, '0')}.${month.padStart(2, '0')}.${year.padStart(4, '0')}`};
}

export function amountValue(value, currency = 'TRY') {
    // Strings/BigInt preserve monetary precision; never parseFloat a currency value.
    let text = digits(value).trim().replace(/\s/g, '').replace(/^(?:₺|\$|€|£)/, '').replace(/(?:TRY|TL|USD|EUR|GBP|₺|\$|€|£)$/i, '');
    if (!/^\d[\d.,]*$/.test(text)) return null;
    let decimals;
    try { decimals = new Intl.NumberFormat('tr-TR', {style: 'currency', currency}).resolvedOptions().maximumFractionDigits; } catch { return null; }
    let integer, fraction = '';
    if (text.includes(',')) {
        if ((text.match(/,/g) || []).length !== 1) return null;
        const parts = text.split(','); integer = parts[0]; fraction = parts[1];
        if (integer.includes('.') && !/^\d{1,3}(\.\d{3})+$/.test(integer)) return null;
        integer = integer.replace(/\./g, '');
    } else if (text.includes('.')) {
        if (/^\d{1,3}(\.\d{3})+$/.test(text)) integer = text.replace(/\./g, '');
        else { const parts = text.split('.'); if (parts.length !== 2) return null; [integer, fraction] = parts; }
    } else integer = text;
    if (!/^\d+$/.test(integer) || !/^\d*$/.test(fraction) || fraction.length > decimals || (/[.,]$/.test(text))) return null;
    integer = integer.replace(/^0+(?=\d)/, ''); fraction = fraction.padEnd(decimals, '0');
    const canonical = integer + (decimals ? `.${fraction}` : '');
    const display = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (decimals ? `,${fraction}` : '');
    return {canonical, display, currency, minor: (BigInt(integer) * 10n ** BigInt(decimals) + BigInt(fraction || '0')).toString()};
}

export function validateField(value, kind = 'text', options = {}) {
    const raw = String(value), text = raw.trim();
    if (!text) return {valid: true, canonical: '', display: '', error: ''}; // No new required-field policy.
    const bad = error => ({valid: false, canonical: null, display: raw, error});
    if (kind === 'identity') {
        const compact = digits(text).replace(/\D/g, '');
        return validIdentity(compact) ? {valid: true, canonical: compact, display: compact, error: ''} : bad('T.C. kimlik numarası 11 rakam olmalı, sıfırla başlamamalı ve kontrol basamakları doğru olmalı.');
    }
    if (kind === 'phone') {
        try {
            const number = phone.parsePhoneNumberFromString(digits(text).replace(/^00/, '+'), {defaultCountry: options.country || 'TR', extract: false});
            if (!number || number.ext || !number.isValid()) return bad('Seçilen ülkeye uygun geçerli bir telefon numarası girin.');
            return {valid: true, canonical: number.number, display: number.formatInternational(), country: number.country, national: number.nationalNumber, error: ''};
        } catch { return bad('Geçerli bir uluslararası telefon numarası girin.'); }
    }
    if (kind === 'date') { const date = dateValue(text); return date ? {valid: true, ...date, error: ''} : bad('Geçerli bir tarih girin: GG.AA.YYYY (ör. 17.10.2026).'); }
    if (kind === 'amount') { const amount = amountValue(text, options.currency); return amount ? {valid: true, ...amount, error: ''} : bad('Tutarı seçilen para biriminin ondalık basamaklarına uygun girin (ör. 1.250,50).'); }
    if (kind === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text) ? {valid: true, canonical: text, display: text, error: ''} : bad('Geçerli bir e-posta adresi girin.');
    return {valid: true, canonical: raw, display: raw, error: ''};
}
