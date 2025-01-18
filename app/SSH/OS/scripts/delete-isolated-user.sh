sudo gpasswd -d __server_user__ __user__
sudo userdel -r "__user__"
echo "User __user__ has been deleted."
