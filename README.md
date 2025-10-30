

# 📖 Tutorial: Setting up Amazon SES Bounce & Complaint Monitoring

This guide shows you how to set up Amazon SES (Simple Email Service) so that **bounces** and **complaints** are sent to your PHP script. The script will log them into files (`bounces.log` and `complaints.log`) so you can see exactly which emails failed or were marked as spam.

NOTE: This is a very basic monitoring system and is meant to built upon. It works as is but is very basic. It is a great way to learn about the good ole' and feared SES bounce rate and how not to get your account shut down.

---

# ✨ Features
Automatic SNS subscription confirmation (via curl).

Logs bounces and complaints to plain text files (bounces.log, complaints.log, sns-debug.log).

Captures full context: subject line, sending source, destinations, bounce/complaint details, SMTP diagnostic codes, reporting MTA, user agent, and arrival date.

Accessible dashboard: HTML page with high‑contrast tables, auto‑refresh every 10 seconds.

No database required — just PHP and flat files.

---

# 🔄 How It Works
SES sends bounce/complaint notifications to SNS topics.

SNS delivers those notifications to your HTTPS endpoint (sns-handler.php).

The PHP script:

Confirms the subscription automatically.

Extracts subject, source, destinations, and event details.

Writes them to bounces.log and complaints.log.

The dashboard (feedback.html) reads those logs and displays them in tables.

---

## 1. Create an SNS Topic

Amazon SNS (Simple Notification Service) is how SES delivers bounce/complaint events to your server.

1. Log in to the [AWS Console](https://console.aws.amazon.com/).
2. Go to **SNS** (Simple Notification Service).
3. Click **Topics** → **Create topic**.
4. Choose **Standard** topic.
5. Give it a name, like:
   - `ses-bounces`
   - `ses-complaints`
6. Click **Create topic**.

👉 You’ll now have an SNS Topic ARN (looks like `arn:aws:sns:us-east-1:123456789012:ses-bounces`).

---

## 2. Create an SNS Subscription

Now we tell SNS where to send the messages.

1. In the SNS console, open your topic (e.g., `ses-bounces`).
2. Click **Create subscription**.
3. For **Protocol**, choose **HTTPS**.
4. For **Endpoint**, enter the public URL where your PHP script lives.  
   Example: `https://yourdomain.com/sns-handler.php`
5. Click **Create subscription**.

⚡ Important: SNS will send a **SubscriptionConfirmation** message to your script.  
Our PHP code uses `curl` to confirm automatically, so you don’t have to click anything.

Repeat the same steps for **complaints** (create another topic called `ses-complaints` and subscribe your script).

---

## 3. Hook Topics into SES

Now we tell SES to send bounce/complaint events to those topics.

1. Go to the **SES Console**.
2. On the left, click **Verified identities**.
3. Choose the domain or email address you’re sending from (e.g., `admin@yourdomain.com`).
4. Click **Edit**.
5. Scroll to **Feedback notifications**.
6. For **Bounces**, choose your `ses-bounces` topic.
7. For **Complaints**, choose your `ses-complaints` topic.
8. Save changes.

---

## 4. Verify Your Identity

SES only lets you send from verified addresses/domains.

1. In **SES → Verified identities**, click **Create identity**.
2. Choose **Domain** if you want to send from all addresses at your domain (recommended).
   - Enter your domain (e.g., `yourdomain.com`).
   - SES will give you DNS records (TXT, CNAME, MX).
   - Add those to your DNS provider (like GoDaddy, Cloudflare, Route53).
3. Or choose **Email address** if you just want one sender (e.g., `admin@yourdomain.com`).
   - SES will send a confirmation email.
   - Click the link inside to verify.

---

## 5. Place the PHP Script on Your Server

1. Copy the `sns-handler.php` script into your web root or any accessible public folder.
2. Make sure it’s accessible at the URL you gave SNS.
3. Give the web server permission to write logs:
   - `bounces.log`
   - `complaints.log`
   - `sns-debug.log`

Example (Linux):

```bash
touch bounces.log complaints.log sns-debug.log
chmod 640 bounces.log complaints.log sns-debug.log
```

---

## 6. Test with SES Simulator

Amazon provides special test addresses:

- **Bounce test**: `bounce@simulator.amazonses.com`
- **Complaint test**: `complaint@simulator.amazonses.com`

Send an email through SES to those addresses. Within a few seconds:

- Your PHP script will log the event.
- You’ll see entries in `bounces.log` or `complaints.log`.
- Your dashboard HTML will display them.

---

## 7. View in the Dashboard

Open your `feedback.html` page in a browser. You’ll see two tables:

- **Bounces**: shows subject, source, destinations, SMTP diagnostic code, etc.
- **Complaints**: shows subject, source, destinations, feedback type, user agent, etc.

The page refreshes every 10 seconds.

---

## 8. Done 🎉

Now you have a full flow:

- SES → SNS → Your PHP script → Logs → Dashboard

This setup helps you **protect your sender reputation** by showing exactly which emails bounced or were marked as spam.

---

### Notes for GitHub README

- Include both the PHP script and the HTML dashboard in your repo.
- Add this tutorial as `README.md`.
- Mention that users must update the **endpoint URL** in SNS to match their server.

---

👉 That’s the whole flow, explained step by step. Would you like me to also draft a **ready‑to‑paste README.md** version with Markdown formatting (headings, code blocks, etc.) so it looks polished on GitHub?
