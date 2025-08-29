from array import array
import numpy as np
import time
from io import BytesIO
import tensorflow as tf
from tensorflow import keras
from tensorflow.keras import layers
from keras.models import load_model
from PIL import Image
from PIL import GifImagePlugin


class captchadecode:
    def __init__(self,h5:str,resolution:tuple,answer_lenght:int,characters:array):
        self.img_width,self.img_height = resolution
        self.prediction_model = load_model(h5)
        # self.prediction_model = load_model("/home/ran/PycharmProjects/capcha_rec/models/pred_model_fns.h5")
        self.max_length = answer_lenght
        self.characters=characters
# print("Number of unique characters: ", len(characters))

# Mapping characters to integers
        self.char_to_num = layers.StringLookup(
            vocabulary=list(self.characters), mask_token=None
        )

        # Mapping integers back to original characters
        self.num_to_char = layers.StringLookup(
            vocabulary=self.char_to_num.get_vocabulary(), mask_token=None, invert=True
        )

    def decode_batch_predictions(self,pred):
        input_len = np.ones(pred.shape[0]) * pred.shape[1]
        # Use greedy search. For complex tasks, you can use beam search
        results = keras.backend.ctc_decode(pred, input_length=input_len, greedy=True)[0][0][
            :, :self.max_length
        ]
        # Iterate over the results and get back the text
        output_text = []
        for res in results:
            res = tf.strings.reduce_join(self.num_to_char(res)).numpy().decode("utf-8")
            output_text.append(res)
        return output_text

    def getFileFromBufer(self,buf):
        img = Image.open(BytesIO(buf)).convert('L')
        img=np.array(img.resize((self.img_width,self.img_height)))
        img = img / 255
        img = np.rollaxis(img, axis=1, start=0)
        img = np.expand_dims(img, axis=0)
        return img
    
    def run(self,buf):
        img=self.getFileFromBufer(buf)
        preds = self.prediction_model.predict(img)
        return self.decode_batch_predictions(preds)


