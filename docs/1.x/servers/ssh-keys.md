# SSH Keys

In order to enable you to deploy other SSH Keys to the server so you can login to the server via SSH with that SSH Keys, Vito has the option of deploying keys to the server.

You can deploy keys directly from the existing ones in your account or you can create a new one and deploy it to the server.

## Login to the servers using your SSH keys

To login to the server, you should use the "_**vito**_" username.

Here is an example on how to add your public key to the authorized keys file on your PC.
```
cat ~/.ssh/id_rsa.pub | ssh your-server-domain 'cat >> .ssh/authorized_keys'
ssh vito@your-server-domain
```