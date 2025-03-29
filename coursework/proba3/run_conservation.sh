#!/bin/bash

# Arguments from PHP
FASTA_FILE="$1"
ALIGN_FILE="$2"
PLOT_FILE="$3"

# Settings
WINDOW_SIZE=4

# Generate log paths next to their outputs
CLUSTALO_LOG="${ALIGN_FILE}_clustalo.log"
PLOTCON_LOG="${PLOT_FILE}_plotcon.log"

echo "Output files:"
echo "  Alignment: $ALIGN_FILE"
echo "  Plot: $PLOT_FILE"
echo "  Logs: $CLUSTALO_LOG, $PLOTCON_LOG"

# Run Clustal Omega
echo "Running Clustal Omega..." > "$CLUSTALO_LOG"
/usr/bin/clustalo -i "$FASTA_FILE" -o "$ALIGN_FILE" --force --threads=1 --verbose >> "$CLUSTALO_LOG" 2>&1

# Check if alignment file was created
if [ ! -s "$ALIGN_FILE" ]; then
  echo "Clustal Omega alignment failed." >> "$CLUSTALO_LOG"
  exit 1
fi

# Run Plotcon
echo "Running Plotcon with window size $WINDOW_SIZE..." > "$PLOTCON_LOG"
/usr/bin/plotcon -sequence "$ALIGN_FILE" -graph png -winsize $WINDOW_SIZE -outseq "$PLOT_FILE" >> "$PLOTCON_LOG" 2>&1

# Check if plot was created
if [ ! -s "$PLOT_FILE" ]; then
  echo "Plotcon plot creation failed." >> "$PLOTCON_LOG"
  exit 1
fi

# Final logs
echo "Alignment saved to: $ALIGN_FILE" >> "$PLOTCON_LOG"
echo "Conservation plot saved to: $PLOT_FILE" >> "$PLOTCON_LOG"

# Optional: list /tmp directory contents
echo "Current /tmp contents:"
ls tmp

