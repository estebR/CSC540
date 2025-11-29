--Question 1
whitespace :: String
whitespace =['\n','\t',' ']

getWord :: String -> String
getWord [] =[]
getWord (x:xs)
  | elem x whitespace= []
  | otherwise= x : getWord xs

dropWord :: String -> String
dropWord []= []
dropWord (x:xs)
  | elem x whitespace= (x:xs)
  | otherwise= dropWord xs

dropSpace :: String -> String
dropSpace [] = []
dropSpace (x:xs)
  | elem x whitespace= dropSpace xs
  | otherwise= (x:xs)

type Word = String
type Line = [Word]

splitWords :: String -> [Word]
splitWords st = split (dropSpace st)
  where
    split []= []
    split st= (getWord st) :split (dropSpace(dropWord st))


--Question 2
lineLen :: Int
lineLen= 30

getLineWords :: Int -> [Word] -> Line
getLineWords len []= []
getLineWords len(w:ws)
  | length w <= len= w : getLineWords (len - (length w + 1)) ws
  | otherwise= []

dropLine :: Int -> [Word] -> [Word]
dropLine len [] = []
dropLine len (w:ws)
  | length w <= len = dropLine (len - (length w + 1)) ws
  | otherwise       = (w:ws)

splitLines :: [Word] -> [Line]
splitLines [] = []
splitLines ws =
    getLineWords lineLen ws
      : splitLines (dropLine lineLen ws)
