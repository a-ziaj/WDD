#!/bin/bash

# Get absolute paths
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
FASTA_FILE="$SCRIPT_DIR/$1"
ALIGN_FILE="$SCRIPT_DIR/$2"
PLOT_FILE="$SCRIPT_DIR/$3"
PLOT_BASE="${PLOT_FILE%.*}"

# Create log files
CLUSTALO_LOG="${ALIGN_FILE%.*}_clustalo.log"
PLOTCON_LOG="${PLOT_BASE}_plotcon.log"

echo "Starting conservation analysis..." > "$PLOTCON_LOG"
echo "FASTA: $FASTA_FILE" >> "$PLOTCON_LOG"
echo "Alignment: $ALIGN_FILE" >> "$PLOTCON_LOG"
echo "Plot: $PLOT_FILE" >> "$PLOTCON_LOG"

# Run Clustal Omega
echo "Running Clustal Omega..." > "$CLUSTALO_LOG"
clustalo -i "$FASTA_FILE" -o "$ALIGN_FILE" --force --threads=1 --verbose >> "$CLUSTALO_LOG" 2>&1

if [ ! -s "$ALIGN_FILE" ]; then
  echo "ERROR: Clustal Omega failed to create alignment" >> "$CLUSTALO_LOG"
  exit 1
fi

# Run plotcon - it will create .1.png file
echo "Running plotcon..." >> "$PLOTCON_LOG"
plotcon -sequences "$ALIGN_FILE" -graph png -winsize 4 -goutfile "$PLOT_BASE" >> "$PLOTCON_LOG" 2>&1

# Handle plotcon's output naming (creates .1.png)
PLOTCON_OUTPUT="${PLOT_BASE}.1.png"
if [ -f "$PLOTCON_OUTPUT" ]; then
  # Copy instead of move to keep original for debugging
  cp "$PLOTCON_OUTPUT" "$PLOT_FILE"
  echo "Copied $PLOTCON_OUTPUT to $PLOT_FILE" >> "$PLOTCON_LOG"
elif [ -f "${PLOT_BASE}.png" ]; then
  cp "${PLOT_BASE}.png" "$PLOT_FILE"
  echo "Copied ${PLOT_BASE}.png to $PLOT_FILE" >> "$PLOTCON_LOG"
else
  echo "ERROR: Failed to create plot file (looked for $PLOTCON_OUTPUT and ${PLOT_BASE}.png)" >> "$PLOTCON_LOG"
  exit 1
fi

echo "Successfully completed analysis" >> "$PLOTCON_LOG"
exit 0
