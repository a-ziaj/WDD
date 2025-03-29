#!/bin/bash

# Arguments: FASTA file, output alignment file, and output plot file
FASTA_FILE="$1"
ALIGN_FILE="$2"
PLOT_FILE="$3"

# Define a default window size (e.g., 4)
WINDOW_SIZE=4

# Create a separate log file for Clustal Omega execution
CLUSTALO_LOG="$(basename $ALIGN_FILE .fasta)_clustalo.log"
PLOTCON_LOG="$(basename $PLOT_FILE .aln)_plotcon_log"

#print output files foe clusao and plotcon
echo "output files: $CLUSTALO_LOG, $PLOTCON_LOG"



# Run Clustal Omega for sequence alignment (logging verbose output)
echo "Running Clustal Omega..." > "$CLUSTALO_LOG"
# Run Clustal Omega and capture both stdout and stderr
/usr/bin/clustalo -i "$FASTA_FILE" -o "$ALIGN_FILE" --force --threads=1 --verbose >> "$CLUSTALO_LOG" 2>&1

$al_content = file_get_contents($ALIGN_FILE);

echo "clustalo: align file: $al_content"
echo "log: $CLUSTALO_LOG"






# Check if Clustal Omega produced a valid output
if [ ! -s "$ALIGN_FILE" ]; then
  echo "Clustal Omega alignment failed. Check logs for details." >> "$CLUSTALO_LOG"
  cat "$CLUSTALO_LOG"
  exit 1
fi

# Run plotcon from EMBOSS to create a conservation plot with the specified window size
echo "Running plotcon for conservation plot with window size: $WINDOW_SIZE..." > "$PLOTCON_LOG"
# Run plotcon and capture both stdout and stderr, specifying the window size and graph format
/usr/bin/plotcon -sequence "$ALIGN_FILE" -graph png -winsize $WINDOW_SIZE -outseq "$PLOT_FILE" >> "$PLOTCON_LOG" 2>&1

# Check if plotcon generated a plot
if [ ! -s "$PLOT_FILE" ]; then
  echo "Plot creation failed. Check logs for details." >> "$PLOTCON_LOG"
  cat "$PLOTCON_LOG"
  exit 1
fi

# Return success message with file paths
echo "Alignment saved to: $ALIGN_FILE" >> "$PLOTCON_LOG"
echo "Conservation plot saved to: $PLOT_FILE" >> "$PLOTCON_LOG"

echo "tmp contents: "$( ls tmp)"

