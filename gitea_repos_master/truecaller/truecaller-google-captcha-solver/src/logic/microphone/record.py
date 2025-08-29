import logging
import wave

import pyaudio
from speech_recognition import Microphone, Recognizer


class RecordAudio:
    @staticmethod
    def record(filename):
        r = Recognizer()

        with Microphone() as source:
            logging.info("Listening sound...")
            # Adjust the ambient noise threshold if needed
            r.adjust_for_ambient_noise(source)

            # Record audio from the microphone
            audio = r.listen(source, 7, phrase_time_limit=6)
            logging.info("Listen stopped")

        # Create a WAV file with the specified parameters
        with wave.open(filename, "wb") as wf:
            wf.setnchannels(1)
            wf.setsampwidth(pyaudio.get_sample_size(pyaudio.paInt16))
            wf.setframerate(44100)

            # Write the audio stream data to the file
            wf.writeframes(audio.get_wav_data())

        logging.info(f"Saved audio to {filename}")
        return filename
