# GitHub webhook ile otomatik dağıtım

Bu kurulumdan sonra `main` dalına yapılan her push, uygulamayı sunucuda otomatik olarak günceller. Webhook yalnızca isteği doğrular ve systemd servisini başlatır; Git, Composer, npm ve Artisan komutları yetkili dağıtım kullanıcısıyla çalışır.

## Bir defalık kurulum

Önce yeni ve güçlü bir webhook anahtarı üretin:

```bash
openssl rand -hex 32
```

Sunucudaki `.env` dosyasına aynı anahtarı ekleyin:

```dotenv
GITHUB_DEPLOY_SECRET=URETILEN_YENI_ANAHTAR
GITHUB_DEPLOY_BRANCH=main
GITHUB_DEPLOY_SERVICE=estate-agent-deploy.service
```

GitHub depo ayarlarındaki webhook için:

- Payload URL: `https://zydemlak.com/github-deploy-webhook`
- Content type: `application/json`
- Secret: `.env` dosyasındaki yeni anahtar
- Event: `Just the push event`
- Active: açık

Ardından sunucuda bir kez çalıştırın:

```bash
cd /var/www/estate-agent
sudo ./scripts/setup-deploy-webhook.sh
php artisan optimize:clear
```

## Kontrol

Servisi webhook kullanıcısının yetkileriyle sınamak için:

```bash
sudo -u www-data sudo -n /usr/bin/systemctl start --no-block estate-agent-deploy.service
sudo journalctl -u estate-agent-deploy.service -n 100 --no-pager
```

Bundan sonraki dağıtımlar için yalnızca yerel değişiklikleri `main` dalına push etmek yeterlidir. Dağıtım çıktısı `journalctl` üzerinden izlenebilir.
