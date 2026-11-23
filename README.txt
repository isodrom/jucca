JUCCA LLC WEBSITE
=================

What is in this folder
----------------------
index.html     The whole site: English and Spanish, mobile menu, contact form.
send.php       Sends contact form messages to juccallc@yahoo.com from the web host.
assets/        Logo and browser icons.
images/        Site photos. IMG-01 (hero) and IMG-02 (about) are included.


How to put it online (GoDaddy)
------------------------------
1. Open your hosting file manager (cPanel > File Manager), or connect with SFTP.
2. Go to the website root folder. It is usually called public_html.
3. Upload everything INSIDE this folder (index.html, send.php, assets, images).
   Keep the names and the folder structure exactly as they are.
4. Visit your domain. The site should load.

The host must run PHP for the contact form. Every GoDaddy Linux and WordPress
hosting plan does.


If WordPress is already installed on the domain
-----------------------------------------------
Do not paste index.html into a WordPress page or a Custom HTML block. The theme
will override the design and the form will not work.

Use one of these instead:
A. Site replaces WordPress: upload the files to the root as above.
B. Site sits beside WordPress: upload this whole folder as a subfolder, for
   example public_html/site/. It will be at yourdomain.com/site/


Test the contact form after uploading
-------------------------------------
1. Open the live site, wait a few seconds, fill in the form, press Send.
2. You should see "Thank you. Your message was sent."
3. Check juccallc@yahoo.com, including the Spam folder.

The form only works on the live host. Opened from your computer by double
clicking index.html, it shows "The message could not be sent".

If messages do not arrive or land in Spam:
- Messages are sent from website@yourdomain. Mark one as "Not spam" in Yahoo.
- Or switch to Web3Forms: get a free access key at web3forms.com using the
  Yahoo address, then paste it into index.html where it says
  data-web3forms-key="" . The form then sends through Web3Forms with a captcha.


Spam protection built in
------------------------
- A hidden field that only bots fill in.
- Messages sent less than 3 seconds after the page loads are dropped.
- Messages with more than two links are dropped.
- At most 5 messages per hour from the same visitor.


Changing things later
---------------------
- Delivery address: first lines of send.php.
- Photos: add IMG-03 to IMG-09 (.jpg or .png) to the images folder. They appear
  automatically. Until then, product cards show an icon and the factory photo
  is hidden.
    IMG-03 Water purification systems     IMG-07 Replacement parts
    IMG-04 Reverse osmosis equipment      IMG-08 Technologies and accessories
    IMG-05 Water softeners                IMG-09 Factory floor (manufacturers)
    IMG-06 Filters and membranes
- Spanish text: the ES table in the script at the end of index.html.
- "Nearly 25 years": appears twice in index.html and twice in the ES table.
