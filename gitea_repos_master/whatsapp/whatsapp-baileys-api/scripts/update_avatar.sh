FILE="node_modules/@whiskeysockets/baileys/lib/Socket/chats.js"

LINE_NUMBER=445

# Define the original and replacement texts
ORIGINAL_TEXT="to: jid,"
REPLACEMENT_TEXT="                target: jid,\n                to: WABinary_1.S_WHATSAPP_NET,"

# Check if the file contains the original text at the specified line
if sed "${LINE_NUMBER}q;d" "$FILE" | grep -q "$ORIGINAL_TEXT"; then
    # Create a backup of the original file
    cp "$FILE" "${FILE}.bak"

    # Use sed to replace the specific line with the new lines
    sed -i "${LINE_NUMBER}s/.*/$REPLACEMENT_TEXT/" "$FILE"

    echo "Line $LINE_NUMBER has been updated. Avatar fixed"
else
    echo "The avatar changes have already been applied. No update needed."
fi