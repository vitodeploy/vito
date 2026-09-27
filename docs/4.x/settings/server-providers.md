# Server Providers

## Introduction

Vito provides integration with multiple server providers to deploy your servers with ease.

When creating new servers, You can select the provider you've connected and deploy your servers with a few clicks.

A connected provider is also used to discover the private networks your servers already belong to — see [Provider Networks](../networks/provider-networks.md).

## Supported Providers

- AWS
- Akamai (Linode)
- Digital Ocean
- Vultr
- Hetzner
- Proxmox VE (self-hosted)
- Custom (Bring your own provider)

## Connecting a Provider

Go to **Settings → Server Providers** and click **Connect**. You can also connect one from the create-server form, with the button next to the **Provider** field.

1. Choose the provider and give the connection a name.
2. Enter the credentials described for your provider below.
3. Choose whether the connection is global (see [Scope](#scope)).

Vito checks the credentials before it saves the connection, so a wrong or under-privileged key is caught right away.

## Provider Setup

### AWS

1. In the AWS console, open **IAM → Users** and create a user for Vito.
2. Give the user the `AmazonEC2FullAccess` managed policy, either directly or through a group.
3. Open the user's **Security credentials** tab and create an access key. AWS asks what the key is for; choose the option for an application running outside AWS.
4. In Vito, enter the **Access Key** (the access key ID) and the **Secret**.

For each server, Vito creates an SSH key pair and a security group in the region you pick.

### Akamai (Linode)

1. In Cloud Manager, open your profile menu and go to **API Tokens → Create a Personal Access Token**.
2. Set these scopes and leave the rest at **No Access**:
   - **Linodes**: Read/Write
   - **Account**: Read Only (Vito uses it to check the token)
   - **VPCs**: Read Only, only needed for [Provider Networks](../networks/provider-networks.md)
3. Pick an expiry. When the token expires, Vito can no longer create or delete servers until you connect a new one.
4. In Vito, enter the token as **Token**.

### Digital Ocean

1. In the control panel, go to **API → Tokens → Generate New Token**.
2. Choose **Full Access**, or **Custom Scopes** with at least:
   - `droplet`: create, read, delete
   - `ssh_key`: create, read, delete
   - `regions`, `sizes` and `image`: read
   - `vpc`: read, only needed for [Provider Networks](../networks/provider-networks.md)
3. In Vito, enter the token as **Token**.

### Vultr

1. In the customer portal, go to **Account → API** and enable the API. The Personal Access Token it shows has full access to your account.
2. Under **Access Control**, add the public IP address of your Vito server, or allow all IPv4 addresses. Vultr rejects API requests from any address that isn't on this list, and Vito then can't connect.
3. In Vito, enter the token as **Token**.

### Hetzner

1. In the Hetzner Cloud Console, open the project you want the servers in.
2. Go to **Security → API Tokens → Generate API token** and choose **Read & Write**.
3. In Vito, enter the token as **Token**.

API tokens belong to one project, so servers are always created in the project that owns the token. Use one connection per project.

### Proxmox VE

Vito creates servers on your own Proxmox VE host or cluster by cloning a cloud-init template for the Ubuntu version you pick. It sets the VM's CPU, memory and disk to the plan, adds a new SSH key through cloud-init, boots it and installs it like any other server.

#### Requirements

- Proxmox VE 8.1 or newer.
- Vito can reach the Proxmox API, on port `8006` of your Proxmox host.
- Vito can reach your servers over SSH (port `22`) on the IP address they get. If Vito runs outside your network, for example on a cloud VPS, and your VMs only get private LAN addresses, Vito can't connect to them. Run Vito on the same network, or connect the networks with a VPN.

#### 1. Create an API token

Run these commands in the Proxmox host's shell. In the web UI, select the node and click **Shell**.

```sh
pveum user add vito@pve --comment "VitoDeploy"
pveum acl modify / --users vito@pve --roles PVEVMAdmin,PVEDatastoreUser,PVESDNUser,PVEAuditor
pveum user token add vito@pve vito --privsep 0
```

The last command prints the token. Keep the `full-tokenid` (`vito@pve!vito`) and the `value`, which is the token secret. Proxmox shows the secret only once.

- `PVEVMAdmin`: clone templates, and configure, start, stop and delete VMs.
- `PVEDatastoreUser`: allocate disk space for the new VMs.
- `PVESDNUser`: attach the VMs to your network bridge.
- `PVEAuditor`: optional. Lets Vito read each node's CPU and memory, so plans that don't fit are greyed out.

:::warning
If you create the token in the web UI instead (**Datacenter → Permissions → API Tokens → Add**), untick **Privilege Separation**. A token with privilege separation doesn't get its user's roles, so it can't see any VMs, and Vito shows **"The API token cannot see any VMs"** when you connect. To keep privilege separation, grant the roles above to the token itself under **Datacenter → Permissions → Add → API Token Permission**, on path `/`.
:::

#### 2. Create a cloud-init template

Create one template for each Ubuntu version you want to use. These commands build an Ubuntu 24.04 template with VM ID `9000`:

```sh
cd /root
wget https://cloud-images.ubuntu.com/noble/current/noble-server-cloudimg-amd64.img

# Only needed for DHCP, see below
apt install -y libguestfs-tools
virt-customize -a noble-server-cloudimg-amd64.img --install qemu-guest-agent

qm create 9000 --name ubuntu-2404-cloud --memory 2048 --cores 2 --ostype l26 \
  --net0 virtio,bridge=vmbr0 --scsihw virtio-scsi-pci
qm set 9000 --scsi0 local-lvm:0,import-from=/root/noble-server-cloudimg-amd64.img
qm set 9000 --ide2 local-lvm:cloudinit --boot order=scsi0 --serial0 socket --vga serial0 --agent 1
qm template 9000
```

Change `9000` to any free VM ID, `vmbr0` to the bridge your servers should use, and `local-lvm` to your storage. Every server copies the template's network device, including its VLAN tag. For another Ubuntu version, repeat the commands with a different VM ID and replace `noble` with `resolute` (26.04), `jammy` (22.04) or `focal` (20.04).

To check a template, run `qm config 9000`. The output should include `template: 1` and a cloud-init drive such as `ide2: local-lvm:vm-9000-cloudinit,media=cdrom`.

:::warning
Build templates from Ubuntu's **cloud images**, as above. A VM installed from an Ubuntu ISO usually ignores the cloud-init settings from Proxmox, so Vito can't log in to servers cloned from it.
:::

**DHCP or static IP.** Each server gets either a static IP you enter when you create it, or an address from your DHCP server. With DHCP, Vito reads the server's IP from the QEMU guest agent, which the cloud images don't include; that's what the `virt-customize` step installs. If you always use static IPs, you can skip it.

**Clusters.** Each online node is a region in Vito. Proxmox can only clone a template to another node when the template is on shared storage, such as Ceph or NFS. With templates on local storage, create servers on the template's node.

#### 3. Connect Proxmox to Vito

| Field | Value |
| --- | --- |
| **API URL** | Your Proxmox address starting with `https://`, for example `https://192.168.1.10:8006`. Anything after the port is ignored. |
| **Disk Storage** | Optional. The storage for the servers' disks. Leave it empty to use the template's storage. |
| **API Token ID** | `vito@pve!vito` |
| **API Token Secret** | The token secret from step 1. |
| **Ubuntu … Template** | The VM ID of your template for each Ubuntu version. Leave the versions you don't use empty; at least one is required. |
| **Verify SSL certificate** | Turn this off if Proxmox still uses its default self-signed certificate. |

When you connect, Vito checks that each mapped VM ID exists, is a QEMU template and has a cloud-init drive. Each **Ubuntu … Template** field has a guide button with the commands for that Ubuntu version, ready to copy.

To add or change templates later, choose **Edit** on the connection. When a template changes, Vito checks all the mapped templates again before saving.

#### Creating servers on Proxmox

When you [create a server](../servers/create.md) on a Proxmox connection:

- **Region** is the Proxmox node.
- **Plan** is one of these sizes:

| Plan | vCPU | Memory | Disk |
| --- | --- | --- | --- |
| xsmall | 1 | 1 GB | 25 GB |
| small | 1 | 2 GB | 50 GB |
| medium | 2 | 4 GB | 80 GB |
| large | 4 | 8 GB | 160 GB |
| xlarge | 8 | 16 GB | 320 GB |
| 2xlarge | 16 | 32 GB | 640 GB |

- **Operating System** must be an Ubuntu version with a template on the connection.
- **Static IP (CIDR)** and **Gateway** set a static address, for example `192.168.1.50/24` and `192.168.1.1`. Leave both empty to use DHCP.

Vito waits up to 10 minutes for the clone, the first boot and SSH. The VM gets `Managed by Vito (server #…)` in its **Notes**; keep that line, because Vito only deletes VMs that carry it. When you delete the server with **Delete from Vito and Proxmox VE**, Vito stops the VM and destroys it along with its disks.

#### Troubleshooting

- **"The API token cannot see any VMs"**: the token has privilege separation turned on; see the warning in step 1.
- **"VM … was not found or the API token cannot access it"**: check the VM ID, and that the roles are granted on `/`.
- **"VM … is not a QEMU template"**: convert the VM with `qm template <id>`. Containers (LXC) aren't supported.
- **"Template … has no cloud-init drive"**: add one with `qm set <id> --ide2 local-lvm:cloudinit`.
- **"Couldn't connect to proxmox"**: check the API URL and port are reachable from the Vito server and the token is right. For a self-signed certificate, turn off **Verify SSL certificate**.
- **"The server did not become reachable within 600 seconds"**: the VM was created but Vito couldn't log in over SSH. Open the VM's **Console** in Proxmox to see which IP it got, then check Vito can reach it on port 22, the guest agent is installed if you use DHCP, and a static IP and gateway match the bridge's network.
- **"Proxmox task failed" or "Proxmox API error"**: the rest of the message comes from Proxmox, for example a full storage or a node that can't reach the template's storage.

### Custom

If your server provider is not listed here, you can use the `Custom` provider when creating a new server.

Your server must have the following requirements so Vito can provision it:

- The server must be running a fresh installation of Ubuntu 20.04, 22.04, 24.04, or 26.04 x64.
- The server must be accessible externally over the Internet.
- The server must have root SSH access enabled.
- The server requirements should meet the following criteria or more: 1 CPU Core with 1GHz, 1GB RAM, and 10GB Disk space.
- The server must have curl installed.

## Scope

Server providers can be created under a specific project or globally.

If you create a server provider under a project, it will only be available for that project.

If you create a server provider globally, it will be available for all projects.

The reason of this feature is when you add a new user to VitoDeploy, you can control which server provider they can
access.

:::info
In any scope, only you will have access to see or use that provider and other users of the project will not be able to see or use it.
:::
