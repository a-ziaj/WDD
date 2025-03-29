#!/bin/bash

# Get absolute paths
SCRIPT_DIR=$(pwd)
FASTA_FILE="$SCRIPT_DIR/$1"
ALIGN_FILE="$SCRIPT_DIR/$2"
PLOT_FILE="$SCRIPT_DIR/$3"
JOB_ID=$(basename "$PLOT_FILE" "_plot.png")

# Create progress file
PROGRESS_FILE="$SCRIPT_DIR/tmp/${JOB_ID}_progress.txt"
echo "0" > "$PROGRESS_FILE"

# Create log files
CLUSTALO_LOG="$SCRIPT_DIR/tmp/${JOB_ID}_clustalo.log"
PLOTCON_LOG="$SCRIPT_DIR/tmp/${JOB_ID}_plotcon.log"

# Function to update progress
update_progress() {
    echo "$1" > "$PROGRESS_FILE"
    echo "Updated progress to $1" >> "$SCRIPT_DIR/tmp/debug.log"
}

# Initialize logs
echo "Starting analysis..." > "$CLUSTALO_LOG"
echo "Starting analysis..." > "$PLOTCON_LOG"
update_progress "5"

# Run Clustal Omega
echo "Running Clustal Omega..." >> "$CLUSTALO_LOG"
update_progress "10"
clustalo -i "$FASTA_FILE" -o "$ALIGN_FILE" --force --threads=1 --verbose >> "$CLUSTALO_LOG" 2>&1
update_progress "50"

# Verify alignment
if [ ! -s "$ALIGN_FILE" ]; then
  echo "ERROR: Alignment failed" >> "$CLUSTALO_LOG"
  update_progress "error:Alignment failed"
  exit 1
fi

# Run Plotcon
echo "Running Plotcon..." >> "$PLOTCON_LOG"
update_progress "60"
plotcon -sequences "$ALIGN_FILE" -graph png -winsize 4 -goutfile "${PLOT_FILE%.*}" >> "$PLOTCON_LOG" 2>&1
update_progress "80"

# Handle plotcon output
if [ -f "${PLOT_FILE%.*}.1.png" ]; then
  mv "${PLOT_FILE%.*}.1.png" "$PLOT_FILE"
elif [ -f "${PLOT_FILE%.*}.png" ]; then
  mv "${PLOT_FILE%.*}.png" "$PLOT_FILE"
else
  echo "ERROR: Plot file not generated" >> "$PLOTCON_LOG"
  update_progress "error:Plot generation failed"
  exit 1
fi

# Complete
update_progress "100"
echo "Analysis completed successfully" >> "$PLOTCON_LOG"
exit 0
