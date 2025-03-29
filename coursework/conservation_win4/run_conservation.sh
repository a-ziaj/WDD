#!/bin/bash

# Input parameters
FASTA_FILE="$1"
ALIGN_FILE="$2"
PLOT_FILE="$3"
WINDOW_SIZE="${4:-4}" # Default to 4 if not provided

# Run Clustal Omega
clustalo -i "$FASTA_FILE" -o "$ALIGN_FILE" --force --threads=1 --verbose

# Check if alignment succeeded
if [ ! -s "$ALIGN_FILE" ]; then
  echo "ERROR: Clustal Omega failed"
  exit 1
fi

# Run Plotcon with specified window size
plotcon -sequences "$ALIGN_FILE" -graph png -winsize "$WINDOW_SIZE" -goutfile "${PLOT_FILE%.*}"

# Handle Plotcon's output file (.1.png)
if [ -f "${PLOT_FILE%.*}.1.png" ]; then
  mv "${PLOT_FILE%.*}.1.png" "$PLOT_FILE"
fi
