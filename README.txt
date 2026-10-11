FLASHCARD DECKS: INSTALL AND UPDATE NOTES
=========================================

FILES
  year8-science.html       115 cards
  year8-history.html        37 cards
  osai-ai-security.html    182 cards
  progress.php             OPTIONAL server storage (Option A). Not needed for Option B.

QUICK START (Option B, no server code)
  1. Upload the .html files to any directory on your site.
  2. Open one. That is all. Nothing needs to be writable and no PHP is needed.

HOW PROGRESS IS REMEMBERED (Option B)
  - Each "I know it" / "Still learning" mark is packed into the page address
    after the #, for example  .../osai-ai-security.html#p=ARk
    The fragment is never sent to your server, so it does not appear in logs.
  - Bookmark the page (or press "Copy my progress link") to carry on from another device.
  - The address changes as cards are marked, so the LATEST link holds the latest progress.
    An older bookmark restores older progress.
  - The page also keeps a copy in the browser's localStorage. Opening the bare
    address (no #) on the same browser restores from that copy. If a link with
    #p=... is opened, the link wins over the copy on that device.
  - Nothing is stored on your server and no personal data is involved.

UPDATING A DECK (adding cards later)
  Every card has a permanent id. Rules (also written at the top of each file):
    1. New cards get the next unused id. The "NEXT FREE ID" number is written
       in the comment above the CARDS list. Update it when you add cards.
    2. Never reuse or renumber an id.
    3. You can freely edit wording, change a card's topic, or move cards around.
       Progress follows the id, not the position.
    4. To remove a card, add r:1 to it, like  {id:57, r:1, t:"web", q:"...", a:"..."}
       Do not delete it, and never give its id to a different card.
    5. Optional note shown under the answer:  n:"small note"
  Everyone's existing links and saved progress keep working after an update.
  New cards simply appear as not yet marked.

  The deck data sits in one block near the top of each file
  (<script id="deck-data">). You can replace that whole block with a new one.

UPGRADING TO SERVER STORAGE (Option A, stable link per user)
  Use this if you want the same URL to always restore progress, even as it changes.
  Needs PHP 7.4 or newer.
    1. Upload progress.php to the same directory as the .html files.
    2. Edit DATA_DIR at the top of progress.php. Choose a folder OUTSIDE the web root
       that PHP can write to, for example /var/www/flashcard-data.
       (If it is inside the web root, the script creates a .htaccess denying access.)
    3. If you add a deck, add its deckId to DECKS in progress.php.
    4. In each .html file, change  apiUrl:""  to  apiUrl:"progress.php"
       The first visit then adds #k=<random code> to the address. The code is the
       user's private key. Bookmark that URL; revisiting it restores progress.
  Notes:
    - Treat the link like a password: anyone who has it can see and change that
      progress. The server stores only a SHA-256 hash of the code as the filename.
    - Use HTTPS.
    - The page and progress.php must be on the same origin (same site).
    - If the server is unreachable the page keeps working and saves locally,
      then syncs next time. If two devices edit, the newer mark for each card wins.
    - Optional cleanup of records untouched for about six months (cron):
        find /var/www/flashcard-data -name '*.json' -mtime +180 -delete
    - Switching a page from B to A starts users on a fresh code; B-style #p= links
      are not imported.

TESTING STATUS
  The pages were tested in a simulated browser (jsdom): marking, restoring from a
  link, opening a bad link, reset, adding and retiring cards after progress was saved,
  and sync between two "devices" against a mock server.
  progress.php was syntax-checked only. It has NOT been run against PHP, so try it
  on your server first (open a page with apiUrl set, mark a card, reload) before
  relying on it.

OPTIONAL
  The pages load the Atkinson Hyperlegible font from Google Fonts. Delete the two
  <link> lines near the top of each file to avoid that; a system font is used instead.
