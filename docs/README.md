sudo nano /etc/systemd/system/log_shutdown.service
sudo systemctl enable log_shutdown.service

sudo systemctl stop log_shutdown.service
cat /var/log/system_times.log

sudo nano /etc/systemd/system/log_start.service
sudo systemctl enable log_start.service

sudo systemctl start log_start.service
cat /var/log/system_times.log
