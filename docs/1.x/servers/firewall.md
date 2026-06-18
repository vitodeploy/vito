# Firewall

Vito installs [UFW](https://help.ubuntu.com/community/UFW) as a firewall service into your server.

## Create new Rule

You can easily create firewall rules and deploy them to your server.

**Rule Type**

Specify the rule type which can be `Allow` or `Deny`

**Protocol**

Select the protocol you want to apply the rule to

**Port**

Incoming port which you are going to apply the rule to

**Source**

The Source is the IP address which you want to apply the rule to.

**Mask**

If the Source is a range then you can specify the range here.

For example if the range is `10.0.0.0/24` then the Source will be `10.0.0.0` and the Mask will be `24`.